<?php
require_once '../config/config.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect(USER_URL);
}

// Google Login URL
$googleClient = getGoogleClient();
$googleLoginUrl = $googleClient->createAuthUrl();

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
                        $token = bin2hex(random_bytes(32));
                        
                        // Simpan token ke database
                        if (setRememberToken($user['id'], $token)) {
                            setcookie('user_remember', $token, time() + (86400 * 30), '/'); // 30 hari
                        }
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
        
        /* Responsivitas untuk mobile */
        @media (max-width: 640px) {
            .main-container {
                padding: 1rem;
                height: auto;
                min-height: 100vh;
            }
            .login-container {
                padding: 1.5rem;
            }
        }
        
        /* Fix untuk iOS */
        @supports (-webkit-touch-callout: none) {
        .main-container {
                min-height: -webkit-fill-available;
            }
        }

        /* Modal overlay style */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100">
    <div class="flex items-center justify-center min-h-screen main-container px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row w-full max-w-5xl overflow-hidden rounded-2xl shadow-2xl">
            <!-- Image Section -->
            <div class="hidden md:block md:w-1/2 bg-cover bg-center login-image" style="background-image: url('<?= ASSETS_URL ?>images/car-login.jpg')">
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
            <div class="w-full md:w-1/2 bg-white p-6 md:p-8 login-container">
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
                
                <div class="relative my-4 flex items-center">
                    <div class="flex-grow border-t border-gray-300"></div>
                    <span class="mx-4 flex-shrink text-gray-500 text-sm">atau</span>
                    <div class="flex-grow border-t border-gray-300"></div>
                </div>
                
                <div>
                    <button id="googleLoginBtn" class="flex items-center justify-center w-full bg-white border border-gray-300 rounded-lg shadow-sm px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512">
                            <path fill="#4285F4" d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"/>
                        </svg>
                        Login dengan Google
                    </button>
                </div>
                
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

    <!-- Modal Popup untuk Google Login -->
    <div id="googleModal" class="modal-overlay">
        <div class="modal-content">
            <div class="text-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Login dengan Google</h3>
                <p class="text-gray-600 text-sm">Silakan tunggu, Anda akan diarahkan ke halaman login Google</p>
            </div>
            <div class="flex justify-center">
                <div class="google-btn-container p-4">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mx-auto mb-4"></div>
                    <iframe id="googleLoginFrame" class="w-full h-96 border-0 hidden" src="about:blank"></iframe>
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

        // Google Login Popup
        document.addEventListener('DOMContentLoaded', function() {
            const googleLoginBtn = document.getElementById('googleLoginBtn');
            const googleModal = document.getElementById('googleModal');
            const googleLoginFrame = document.getElementById('googleLoginFrame');
            const googleLoginUrl = '<?= $googleLoginUrl ?>';
            
            googleLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Tampilkan modal
                googleModal.style.display = 'flex';
                
                // Ada dua cara untuk login dengan Google:
                // 1. Menggunakan popup window (lebih umum)
                // 2. Menggunakan iframe (kadang diblokir oleh kebijakan keamanan)
                
                // Cara 1: Popup Window (lebih direkomendasikan)
                const width = 500;
                const height = 600;
                const left = (window.innerWidth - width) / 2;
                const top = (window.innerHeight - height) / 2;
                
                const popup = window.open(
                    googleLoginUrl,
                    'googleLogin',
                    `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes,status=yes`
                );
                
                // Periksa apakah popup berhasil dibuka
                if (!popup || popup.closed || typeof popup.closed === 'undefined') {
                    // Popup diblokir, gunakan redirect normal
                    googleModal.style.display = 'none';
                    window.location.href = googleLoginUrl;
                    return;
                }
                
                // Interval untuk memeriksa apakah popup sudah ditutup
                const checkPopup = setInterval(function() {
                    if (popup.closed) {
                        clearInterval(checkPopup);
                        googleModal.style.display = 'none';
                        // Refresh halaman setelah login
                        window.location.reload();
                    }
                }, 500);
                
                // Sembunyikan modal saat mengklik di luar modal
                googleModal.addEventListener('click', function(e) {
                    if (e.target === googleModal) {
                        googleModal.style.display = 'none';
                        if (!popup.closed) {
                            popup.close();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html> 