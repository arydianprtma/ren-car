<?php
require_once '../config/config.php';

// Cek apakah user sudah login, jika belum redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Silakan login terlebih dahulu.";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "login.php");
    exit;
}

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil data user dari database
$userId = $_SESSION['user_id'];
$user = [];
try {
    $stmt = $conn->prepare("
        SELECT id, username, nama, email, no_telp, alamat, no_ktp, foto_ktp, created_at 
        FROM users 
        WHERE id = :id
    ");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL);
    exit;
}

// Cek apakah form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_telp = trim($_POST['no_telp']);
    $alamat = trim($_POST['alamat']);
    $password_lama = trim($_POST['password_lama']);
    $password_baru = trim($_POST['password_baru']);
    $konfirmasi_password = trim($_POST['konfirmasi_password']);
    
    // Validasi input
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = "Nama tidak boleh kosong";
    }
    
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } elseif ($email !== $user['email']) {
        // Cek apakah email sudah digunakan oleh user lain
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email sudah digunakan";
        }
    }
    
    if (empty($no_telp)) {
        $errors[] = "Nomor telepon tidak boleh kosong";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $no_telp)) {
        $errors[] = "Nomor telepon harus berisi 10-15 digit angka";
    }
    
    if (empty($alamat)) {
        $errors[] = "Alamat tidak boleh kosong";
    }
    
    // Jika user ingin mengubah password
    $update_password = false;
    if (!empty($password_baru)) {
        if (empty($password_lama)) {
            $errors[] = "Password lama harus diisi";
        } else {
            // Verifikasi password lama
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $hashed_password = $stmt->fetchColumn();
            
            if (!password_verify($password_lama, $hashed_password)) {
                $errors[] = "Password lama tidak sesuai";
            }
        }
        
        if (strlen($password_baru) < 6) {
            $errors[] = "Password baru minimal 6 karakter";
        }
        
        if ($password_baru !== $konfirmasi_password) {
            $errors[] = "Konfirmasi password tidak sesuai";
        }
        
        $update_password = true;
    }
    
    // Upload foto KTP jika ada
    $foto_ktp = $user['foto_ktp']; // Default tetap menggunakan foto KTP yang lama
    if (!empty($_FILES['foto_ktp']['name'])) {
        $target_dir = "../assets/images/ktp/";
        
        // Pastikan direktori ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['foto_ktp']['name'], PATHINFO_EXTENSION));
        $new_filename = "ktp_" . $userId . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Validasi file
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Format file KTP tidak valid. Hanya JPG, JPEG, dan PNG yang diperbolehkan.";
        } elseif ($_FILES['foto_ktp']['size'] > $max_file_size) {
            $errors[] = "Ukuran file KTP terlalu besar. Maksimal 5MB.";
        } else {
            // Upload file
            if (move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $target_file)) {
                // Hapus foto lama jika ada
                if (!empty($user['foto_ktp']) && file_exists($target_dir . $user['foto_ktp'])) {
                    unlink($target_dir . $user['foto_ktp']);
                }
                $foto_ktp = $new_filename;
            } else {
                $errors[] = "Gagal mengupload file KTP.";
            }
        }
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        try {
            // Memulai transaksi
            $conn->beginTransaction();
            
            // Update data profil
            $stmt = $conn->prepare("
                UPDATE users 
                SET nama = :nama, email = :email, no_telp = :no_telp, alamat = :alamat, foto_ktp = :foto_ktp
                WHERE id = :id
            ");
            $stmt->bindParam(':nama', $nama, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':no_telp', $no_telp, PDO::PARAM_STR);
            $stmt->bindParam(':alamat', $alamat, PDO::PARAM_STR);
            $stmt->bindParam(':foto_ktp', $foto_ktp, PDO::PARAM_STR);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Update password jika diperlukan
            if ($update_password) {
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            // Commit transaksi
            $conn->commit();
            
            // Update session data
            $_SESSION['user_nama'] = $nama;
            $_SESSION['user_email'] = $email;
            
            // Set flash message sukses
            $_SESSION['flash_message'] = "Profil berhasil diperbarui.";
            $_SESSION['flash_type'] = "green";
            
            // Redirect untuk refresh data
            header("Location: " . USER_URL . "profil.php");
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaksi jika terjadi error
            $conn->rollBack();
            $_SESSION['flash_message'] = "Gagal memperbarui profil: " . $e->getMessage();
            $_SESSION['flash_type'] = "red";
        }
    } else {
        $_SESSION['flash_message'] = implode("<br>", $errors);
        $_SESSION['flash_type'] = "red";
    }
}

// Include header setelah semua proses header() selesai
require_once 'includes/header.php';
?>

<!-- Profil Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar / Profile Card -->
            <div class="w-full md:w-1/3">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 sticky top-24">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 rounded-full bg-blue-100 mx-auto mb-4 flex items-center justify-center">
                            <i class="fas fa-user-circle text-blue-500 text-5xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($user['nama']) ?></h3>
                        <p class="text-gray-600 text-sm"><?= htmlspecialchars($user['email']) ?></p>
                        <p class="text-gray-500 text-xs mt-1">
                            Member sejak <?= date("d F Y", strtotime($user['created_at'])) ?>
                        </p>
                    </div>
                    
                    <div class="border-t border-gray-100 pt-4">
                        <div class="flex items-center py-2">
                            <div class="w-10 text-center text-blue-500">
                                <i class="fas fa-phone"></i>
                            </div>
                            <span class="text-gray-800"><?= htmlspecialchars($user['no_telp']) ?></span>
                        </div>
                        <div class="flex items-center py-2">
                            <div class="w-10 text-center text-blue-500">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <span class="text-gray-800"><?= htmlspecialchars($user['alamat']) ?></span>
                        </div>
                        <div class="flex items-center py-2">
                            <div class="w-10 text-center text-blue-500">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <span class="text-gray-800"><?= htmlspecialchars($user['no_ktp']) ?></span>
                        </div>
                    </div>

                    <?php if (!empty($user['foto_ktp'])): ?>
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Foto KTP</h4>
                        <div class="relative rounded-lg overflow-hidden border border-gray-200">
                            <img src="<?= ASSETS_URL ?>images/ktp/<?= htmlspecialchars($user['foto_ktp']) ?>" alt="KTP" class="w-full h-auto">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Content / Edit Profile Form -->
            <div class="w-full md:w-2/3">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Edit Profil</h2>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="nama" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                                <input type="text" id="nama" name="nama" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?= htmlspecialchars($user['nama']) ?>" required>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                                <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            
                            <div>
                                <label for="no_telp" class="block text-gray-700 text-sm font-medium mb-2">Nomor Telepon</label>
                                <input type="text" id="no_telp" name="no_telp" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?= htmlspecialchars($user['no_telp']) ?>" required>
                            </div>
                            
                            <div>
                                <label for="no_ktp" class="block text-gray-700 text-sm font-medium mb-2">Nomor KTP</label>
                                <input type="text" id="no_ktp" name="no_ktp" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-100" value="<?= htmlspecialchars($user['no_ktp']) ?>" disabled>
                                <p class="text-xs text-gray-500 mt-1">Nomor KTP tidak dapat diubah.</p>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="alamat" class="block text-gray-700 text-sm font-medium mb-2">Alamat</label>
                            <textarea id="alamat" name="alamat" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required><?= htmlspecialchars($user['alamat']) ?></textarea>
                        </div>
                        
                        <div class="mb-6">
                            <label for="foto_ktp" class="block text-gray-700 text-sm font-medium mb-2">Foto KTP</label>
                            <div class="flex items-center">
                                <input type="file" id="foto_ktp" name="foto_ktp" class="hidden" accept="image/jpeg, image/png">
                                <label for="foto_ktp" class="cursor-pointer bg-white px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                    <i class="fas fa-upload mr-2"></i> Pilih File
                                </label>
                                <span id="file_name" class="ml-3 text-sm text-gray-600">Tidak ada file dipilih</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Format: JPG, JPEG, PNG. Maksimal 5MB.</p>
                            <?php if (!empty($user['foto_ktp'])): ?>
                            <p class="text-xs text-blue-600 mt-1">Anda sudah memiliki foto KTP. Upload baru untuk mengganti.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="border-t border-gray-200 my-6 pt-6">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Ubah Password</h3>
                            <p class="text-sm text-gray-600 mb-4">Biarkan kosong jika Anda tidak ingin mengubah password.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="password_lama" class="block text-gray-700 text-sm font-medium mb-2">Password Lama</label>
                                    <input type="password" id="password_lama" name="password_lama" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div></div>
                                
                                <div>
                                    <label for="password_baru" class="block text-gray-700 text-sm font-medium mb-2">Password Baru</label>
                                    <input type="password" id="password_baru" name="password_baru" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div>
                                    <label for="konfirmasi_password" class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password Baru</label>
                                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menampilkan nama file yang dipilih
    const fileInput = document.getElementById('foto_ktp');
    const fileNameDisplay = document.getElementById('file_name');
    
    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            fileNameDisplay.textContent = this.files[0].name;
        } else {
            fileNameDisplay.textContent = 'Tidak ada file dipilih';
        }
    });
});
</script>

<?php include_once 'includes/footer.php'; ?> 