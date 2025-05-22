<?php
require_once '../config/config.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect(USER_URL);
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $_SESSION['flash_message'] = 'Username dan password harus diisi!';
        $_SESSION['flash_type'] = 'red';
    } else {
        try {
            // Cek login
            $db = new Database();
            $conn = $db->getConnection();
            
            if (!$conn) {
                throw new Exception("Koneksi database gagal");
            }
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE (username = :username OR email = :email) AND status = 'aktif'");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password'])) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_nama'] = $user['nama'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Set cookie jika remember me
                    if ($remember) {
                        $token = generateRandomString(32);
                        setcookie('user_remember', $token, time() + (86400 * 30), '/'); // 30 hari
                        
                        // Simpan token di database jika diperlukan
                        // ...
                    }
                    
                    // Redirect ke homepage
                    redirect(USER_URL);
                } else {
                    $_SESSION['flash_message'] = 'Password salah! Silakan periksa kembali password Anda.';
                    $_SESSION['flash_type'] = 'red';
                }
            } else {
                // Coba cek apakah ada user dengan username tapi status nonaktif
                $stmt = $conn->prepare("SELECT * FROM users WHERE (username = :username OR email = :email) AND status = 'nonaktif'");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = 'Akun Anda dinonaktifkan, silakan hubungi admin!';
                    $_SESSION['flash_type'] = 'red';
                } else {
                    $_SESSION['flash_message'] = 'Username atau email tidak ditemukan!';
                    $_SESSION['flash_type'] = 'red';
                }
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Terjadi kesalahan saat login. Silakan coba lagi.';
            $_SESSION['flash_type'] = 'red';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rental Mobil</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            overflow: hidden;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .login-image {
            background-position: center 25%;
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
        .main-container {
            height: 100vh;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100">
    <div class="flex items-center justify-center h-full main-container px-4 sm:px-6 lg:px-8">
        <div class="flex w-full max-w-5xl overflow-hidden rounded-2xl shadow-2xl">
            <!-- Image Section -->
            <div class="hidden lg:block lg:w-1/2 bg-cover bg-center login-image" style="background-image: url('<?= ASSETS_URL ?>images/car-login.jpg')">
                <div class="flex h-full items-center justify-center p-8">
                    <div class="text-center">
                        <h1 class="text-3xl font-bold text-white mb-4">Rental Mobil Terbaik</h1>
                        <p class="text-xl text-white mb-6">Sewa mobil dengan mudah dan harga terjangkau.</p>
                        <div class="mx-auto h-1 w-24 bg-blue-500 rounded-full mb-6"></div>
                        <div class="grid grid-cols-2 gap-3 text-white">
                            <div class="rounded-lg border border-white/20 bg-white/10 p-3">
                                <i class="fas fa-car-side text-blue-400 text-2xl mb-1"></i>
                                <h3 class="text-lg font-semibold">Berbagai Pilihan</h3>
                                <p class="text-white/80 text-sm">Beragam jenis mobil siap pakai</p>
                            </div>
                            <div class="rounded-lg border border-white/20 bg-white/10 p-3">
                                <i class="fas fa-money-bill-wave text-blue-400 text-2xl mb-1"></i>
                                <h3 class="text-lg font-semibold">Harga Terjangkau</h3>
                                <p class="text-white/80 text-sm">Kualitas terbaik, biaya hemat</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Section -->
            <div class="w-full lg:w-1/2 bg-white p-6 md:p-8 login-container">
                <div class="mb-6 text-center">
                    <a href="<?= BASE_URL ?>" class="flex justify-center items-center mb-4">
                        <i class="fas fa-car-side text-blue-600 text-3xl mr-2"></i>
                        <span class="text-2xl font-bold text-gray-800">Rental Mobil</span>
                    </a>
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-1">Selamat Datang Kembali</h2>
                    <p class="text-gray-600 text-sm">Masuk untuk melanjutkan ke akun Anda</p>
                </div>
                
                <?php if(isset($_SESSION['flash_message'])): ?>
                <div class="mb-4 rounded-lg bg-<?= $_SESSION['flash_type'] ?>-100 border border-<?= $_SESSION['flash_type'] ?>-400 text-<?= $_SESSION['flash_type'] ?>-700 px-4 py-2 relative" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['flash_message'] ?></span>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username atau Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-blue-500"></i>
                            </div>
                            <input type="text" id="username" name="username" class="form-input pl-10 w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan username atau email" required>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <a href="lupa-password.php" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Lupa password?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-blue-500"></i>
                            </div>
                            <input type="password" id="password" name="password" class="form-input pl-10 w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">Ingat saya</label>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn-primary w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">Masuk</button>
                    </div>
                </form>
                
                <div class="mt-4 text-center">
                    <p class="text-gray-600">Belum punya akun? <a href="register.php" class="text-blue-600 hover:text-blue-800 font-medium">Daftar sekarang</a></p>
                </div>
                
                <div class="mt-5 border-t border-gray-200 pt-4">
                    <div class="flex items-center justify-center">
                        <a href="<?= BASE_URL ?>" class="flex items-center text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Beranda
                        </a>
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