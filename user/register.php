<?php
require_once '../config/config.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect(USER_URL);
}

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama = sanitize($_POST['nama']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    $no_telp = sanitize($_POST['no_telp']);
    $alamat = sanitize($_POST['alamat']);
    $no_ktp = sanitize($_POST['no_ktp']);
    
    // Validasi input
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = 'Nama harus diisi!';
    }
    
    if (empty($username)) {
        $errors[] = 'Username harus diisi!';
    } else if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username hanya boleh berisi huruf, angka, dan underscore!';
    }
    
    if (empty($email)) {
        $errors[] = 'Email harus diisi!';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid!';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi!';
    } else if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter!';
    }
    
    if ($password !== $konfirmasi_password) {
        $errors[] = 'Konfirmasi password tidak cocok!';
    }
    
    if (empty($no_telp)) {
        $errors[] = 'No. Telepon harus diisi!';
    } else if (!preg_match('/^[0-9]{10,15}$/', $no_telp)) {
        $errors[] = 'No. Telepon tidak valid!';
    }
    
    if (empty($alamat)) {
        $errors[] = 'Alamat harus diisi!';
    }
    
    if (empty($no_ktp)) {
        $errors[] = 'No. KTP harus diisi!';
    } else if (!preg_match('/^[0-9]{16}$/', $no_ktp)) {
        $errors[] = 'No. KTP harus 16 digit angka!';
    }
    
    // Cek apakah ada upload foto KTP
    $foto_ktp = null;
    if (isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['foto_ktp']['type'];
        $file_size = $_FILES['foto_ktp']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Format foto KTP harus JPG, JPEG, atau PNG!';
        } else if ($file_size > 2000000) { // 2MB
            $errors[] = 'Ukuran foto KTP maksimal 2MB!';
        } else {
            // Generate unique filename
            $file_ext = pathinfo($_FILES['foto_ktp']['name'], PATHINFO_EXTENSION);
            $foto_ktp = 'ktp_' . time() . '_' . uniqid() . '.' . $file_ext;
            
            // Upload path
            $upload_path = '../assets/uploads/ktp/';
            
            // Create directory if not exists
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $upload_path . $foto_ktp)) {
                $errors[] = 'Gagal upload foto KTP!';
                $foto_ktp = null;
            }
        }
    } else {
        $errors[] = 'Foto KTP harus diupload!';
    }
    
    // Jika tidak ada error, lanjutkan proses registrasi
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Cek apakah username sudah terdaftar
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Username sudah terdaftar!';
        } else {
            // Cek apakah email sudah terdaftar
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Email sudah terdaftar!';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Simpan user baru
                $stmt = $conn->prepare("INSERT INTO users (nama, username, email, password, no_telp, alamat, no_ktp, foto_ktp) VALUES (:nama, :username, :email, :password, :no_telp, :alamat, :no_ktp, :foto_ktp)");
                $stmt->bindParam(':nama', $nama);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':no_telp', $no_telp);
                $stmt->bindParam(':alamat', $alamat);
                $stmt->bindParam(':no_ktp', $no_ktp);
                $stmt->bindParam(':foto_ktp', $foto_ktp);
                
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = 'Registrasi berhasil! Silakan login.';
                    $_SESSION['flash_type'] = 'green';
                    redirect(USER_URL . 'login.php');
                } else {
                    $errors[] = 'Terjadi kesalahan saat mendaftar!';
                    
                    // Hapus file yang sudah diupload jika gagal
                    if ($foto_ktp && file_exists('../assets/uploads/ktp/' . $foto_ktp)) {
                        unlink('../assets/uploads/ktp/' . $foto_ktp);
                    }
                }
            }
        }
    }
    
    // Jika ada error, simpan ke session untuk ditampilkan
    if (!empty($errors)) {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'red';
        
        // Hapus file yang sudah diupload jika gagal
        if ($foto_ktp && file_exists('../assets/uploads/ktp/' . $foto_ktp)) {
            unlink('../assets/uploads/ktp/' . $foto_ktp);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Rental Mobil</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .register-container {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .register-image {
            background-position: center 35%;
            box-shadow: inset 0 0 0 2000px rgba(0, 0, 0, 0.6);
        }
        .btn-primary {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(37, 99, 235, 0.4);
        }
        .custom-file-input::-webkit-file-upload-button {
            visibility: hidden;
            width: 0;
        }
        .custom-file-input::before {
            content: 'Pilih File';
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 8px 12px;
            outline: none;
            white-space: nowrap;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.875rem;
            color: #4b5563;
            margin-right: 10px;
        }
        .custom-file-input:hover::before {
            background: #e5e7eb;
        }
        .custom-file-input:active::before {
            background: #d1d5db;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen">
    <div class="flex min-h-screen items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="flex w-full max-w-6xl overflow-hidden rounded-2xl shadow-2xl">
            <!-- Form Section -->
            <div class="w-full lg:w-3/5 bg-white p-8 md:p-12 register-container">
                <div class="mb-8 text-center">
                    <a href="<?= BASE_URL ?>" class="flex justify-center items-center mb-6">
                        <i class="fas fa-car-side text-blue-600 text-3xl mr-2"></i>
                        <span class="text-2xl font-bold text-gray-800">Rental Mobil</span>
                    </a>
                    <h2 class="text-3xl font-extrabold text-gray-900 mb-2">Buat Akun Baru</h2>
                    <p class="text-gray-600">Daftar untuk menyewa mobil dengan mudah</p>
                </div>
                
                <?php if(isset($_SESSION['flash_message'])): ?>
                <div class="mb-6 rounded-lg bg-<?= $_SESSION['flash_type'] ?>-100 border border-<?= $_SESSION['flash_type'] ?>-400 text-<?= $_SESSION['flash_type'] ?>-700 px-4 py-3 relative" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['flash_message'] ?></span>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-blue-500"></i>
                                </div>
                                <input type="text" id="nama" name="nama" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan nama lengkap" value="<?= $_POST['nama'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-at text-blue-500"></i>
                                </div>
                                <input type="text" id="username" name="username" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan username" value="<?= $_POST['username'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-blue-500"></i>
                                </div>
                                <input type="email" id="email" name="email" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan email" value="<?= $_POST['email'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="no_telp" class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-blue-500"></i>
                                </div>
                                <input type="text" id="no_telp" name="no_telp" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan no. telepon" value="<?= $_POST['no_telp'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-blue-500"></i>
                                </div>
                                <input type="password" id="password" name="password" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan password" required>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Minimal 6 karakter</p>
                        </div>
                        
                        <div>
                            <label for="konfirmasi_password" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-shield-alt text-blue-500"></i>
                                </div>
                                <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan konfirmasi password" required>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                            <div class="relative">
                                <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                                    <i class="fas fa-map-marker-alt text-blue-500"></i>
                                </div>
                                <textarea id="alamat" name="alamat" rows="3" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan alamat lengkap" required><?= $_POST['alamat'] ?? '' ?></textarea>
                            </div>
                        </div>
                        
                        <div>
                            <label for="no_ktp" class="block text-sm font-medium text-gray-700 mb-1">No. KTP</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-id-card text-blue-500"></i>
                                </div>
                                <input type="text" id="no_ktp" name="no_ktp" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan no. KTP (16 digit)" value="<?= $_POST['no_ktp'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="foto_ktp" class="block text-sm font-medium text-gray-700 mb-1">Foto KTP</label>
                            <div class="relative border border-gray-300 rounded-lg px-4 py-3 bg-white">
                                <input type="file" id="foto_ktp" name="foto_ktp" class="custom-file-input w-full focus:outline-none" accept="image/*" required>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Upload foto KTP (JPG, JPEG, PNG, maks 2MB)</p>
                        </div>
                    </div>
                    
                    <div class="pt-3">
                        <button type="submit" class="btn-primary w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">Daftar</button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600">Sudah punya akun? <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">Login</a></p>
                </div>
                
                <div class="mt-8 border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-center">
                        <a href="<?= BASE_URL ?>" class="flex items-center text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Image Section -->
            <div class="hidden lg:block lg:w-2/5 bg-cover bg-center register-image" style="background-image: url('<?= ASSETS_URL ?>images/car-register.jpg')">
                <div class="flex h-full items-center justify-center p-12">
                    <div class="text-center">
                        <h1 class="text-4xl font-bold text-white mb-6">Rental Mobil Terbaik</h1>
                        <p class="text-xl text-white mb-8">Daftar sekarang dan nikmati layanan rental mobil terbaik dengan harga terjangkau.</p>
                        <div class="mx-auto h-1 w-24 bg-blue-500 rounded-full mb-8"></div>
                        <ul class="space-y-4 text-left">
                            <li class="flex items-center">
                                <div class="rounded-full bg-blue-500/20 p-2 mr-3">
                                    <i class="fas fa-check text-blue-300"></i>
                                </div>
                                <span class="text-white text-lg">Berbagai pilihan mobil</span>
                            </li>
                            <li class="flex items-center">
                                <div class="rounded-full bg-blue-500/20 p-2 mr-3">
                                    <i class="fas fa-check text-blue-300"></i>
                                </div>
                                <span class="text-white text-lg">Proses cepat dan mudah</span>
                            </li>
                            <li class="flex items-center">
                                <div class="rounded-full bg-blue-500/20 p-2 mr-3">
                                    <i class="fas fa-check text-blue-300"></i>
                                </div>
                                <span class="text-white text-lg">Bebas biaya administrasi</span>
                            </li>
                            <li class="flex items-center">
                                <div class="rounded-full bg-blue-500/20 p-2 mr-3">
                                    <i class="fas fa-check text-blue-300"></i>
                                </div>
                                <span class="text-white text-lg">Layanan 24/7</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide flash messages after 3 seconds
        setTimeout(function() {
            const alert = document.querySelector('[role="alert"]');
            if (alert) {
                alert.style.transition = 'opacity 1s';
                alert.style.opacity = 0;
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }
        }, 3000);
    </script>
</body>
</html> 