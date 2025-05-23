<?php
/**
 * Halaman Profil Admin
 */
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil ID admin dari sesi
$adminId = $_SESSION['admin_id'] ?? 0;

// Jika ID admin tidak ditemukan, redirect ke halaman login
if ($adminId <= 0) {
    setFlashMessage("Sesi telah berakhir. Silakan login kembali.", "red");
    redirect(ADMIN_URL . "login.php");
    exit;
}

// Ambil data admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika admin tidak ditemukan, redirect ke login
if (!$admin) {
    setFlashMessage("Data admin tidak ditemukan. Silakan login kembali.", "red");
    redirect(ADMIN_URL . "login.php");
    exit;
}

// Proses update profile jika ada request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $errors = [];
    
    // Validasi input
    if (empty($nama)) $errors[] = "Nama tidak boleh kosong";
    if (empty($email)) $errors[] = "Email tidak boleh kosong";
    if (empty($username)) $errors[] = "Username tidak boleh kosong";
    
    // Validasi email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    // Cek keunikan email dan username (kecuali untuk admin yang sedang login)
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $adminId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email sudah digunakan oleh pengguna lain";
        }
    }
    
    if (!empty($username)) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $adminId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username sudah digunakan oleh pengguna lain";
        }
    }
    
    // Jika ada error, tampilkan pesan error
    if (!empty($errors)) {
        $errorMessage = implode('<br>', $errors);
        setFlashMessage($errorMessage, "red");
    } else {
        try {
            // Update data profil admin
            $updateSql = "UPDATE users SET nama = ?, email = ?, no_telp = ?, username = ? WHERE id = ?";
            $updateParams = [$nama, $email, $no_telp, $username, $adminId];
            
            // Jika password diisi, hash password baru
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateSql = "UPDATE users SET nama = ?, email = ?, no_telp = ?, username = ?, password = ? WHERE id = ?";
                $updateParams = [$nama, $email, $no_telp, $username, $hashedPassword, $adminId];
            }
            
            $stmt = $conn->prepare($updateSql);
            $stmt->execute($updateParams);
            
            // Update data admin di session
            $_SESSION['admin_nama'] = $nama;
            
            setFlashMessage("Profil berhasil diperbarui", "green");
            redirect(ADMIN_URL . "profile.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage("Terjadi kesalahan: " . $e->getMessage(), "red");
        }
    }
}
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800 flex items-center">
        <i class="fas fa-user-circle mr-3 text-primary-600"></i> Profil Admin
    </h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Kartu Profil -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-center mb-6">
            <div class="w-24 h-24 bg-primary-100 text-primary-600 rounded-full mx-auto flex items-center justify-center text-3xl mb-3">
                <i class="fas fa-user"></i>
            </div>
            <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($admin['nama']) ?></h2>
            <p class="text-gray-500 text-sm">Administrator</p>
        </div>
        
        <div class="border-t border-gray-200 pt-4">
            <ul class="space-y-3 text-sm">
                <li class="flex items-start">
                    <i class="fas fa-envelope text-gray-400 mt-1 mr-3 w-5"></i>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Email</p>
                        <p class="text-gray-800"><?= htmlspecialchars($admin['email']) ?></p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-phone text-gray-400 mt-1 mr-3 w-5"></i>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Telepon</p>
                        <p class="text-gray-800"><?= htmlspecialchars($admin['no_telp'] ?? 'Belum diisi') ?></p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-user-tag text-gray-400 mt-1 mr-3 w-5"></i>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Username</p>
                        <p class="text-gray-800"><?= htmlspecialchars($admin['username']) ?></p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-calendar-alt text-gray-400 mt-1 mr-3 w-5"></i>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Terdaftar Sejak</p>
                        <p class="text-gray-800"><?= date('d F Y', strtotime($admin['created_at'])) ?></p>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Form Edit Profil -->
    <div class="bg-white rounded-lg shadow-sm p-6 md:col-span-2">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Edit Profil</h2>
        
        <form action="" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($admin['nama']) ?>" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label for="no_telp" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
                    <input type="text" id="no_telp" name="no_telp" value="<?= htmlspecialchars($admin['no_telp'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
            
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h3 class="text-md font-medium text-gray-800 mb-3">Ubah Password</h3>
                <p class="text-sm text-gray-500 mb-4">Kosongkan bidang ini jika tidak ingin mengubah password</p>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input type="password" id="password" name="password" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Masukkan password baru">
                </div>
            </div>
            
            <div class="flex justify-end pt-4">
                <input type="hidden" name="update_profile" value="1">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-6 rounded-lg shadow-sm transition duration-200 flex items-center">
                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 