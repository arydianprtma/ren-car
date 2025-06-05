<?php
require_once '../config/config.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect(USER_URL);
}

// Inisialisasi database
$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';
$step = 'request'; // request atau reset

// Cek apakah ada token reset dari URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    // Verifikasi token
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW() AND status = 'aktif'");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $step = 'reset';
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = 'Token reset password tidak valid atau sudah kadaluarsa.';
        $messageType = 'red';
    }
}

// Proses request reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $message = 'Email harus diisi!';
        $messageType = 'red';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid!';
        $messageType = 'red';
    } else {
        try {
            // Cek apakah email ada di database
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND status = 'aktif'");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate token reset
                $resetToken = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Berlaku 1 jam
                
                // Simpan token ke database
                $updateStmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE id = :id");
                $updateStmt->bindParam(':token', $resetToken);
                $updateStmt->bindParam(':expires', $expires);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // URL reset password
                $resetUrl = BASE_URL . 'user/lupa-password.php?token=' . $resetToken;
                
                // Kirim email (simulasi - dalam produksi gunakan PHPMailer atau library email lainnya)
                $emailContent = "
                    Halo {$user['nama']},
                    
                    Anda telah meminta reset password untuk akun Rental Mobil Anda.
                    
                    Klik link berikut untuk mereset password:
                    $resetUrl
                    
                    Link ini akan kadaluarsa dalam 1 jam.
                    
                    Jika Anda tidak meminta reset password, abaikan email ini.
                    
                    Terima kasih,
                    Tim Rental Mobil
                ";
                
                // Log email content untuk development (hapus di production)
                error_log("Reset Password Email untuk {$email}:\n" . $emailContent);
                
                // Link demo email untuk development (hapus di production)
                $demoEmailUrl = BASE_URL . 'user/demo-email.php?token=' . $resetToken . '&email=' . urlencode($email);
                error_log("Demo Email URL: " . $demoEmailUrl);
                
                $message = 'Link reset password telah dikirim ke email Anda. Silakan periksa inbox atau folder spam.';
                // Untuk development, tambahkan link demo
                if (ini_get('display_errors')) {
                    $message .= '<br><small><a href="' . $demoEmailUrl . '" target="_blank" style="color: #1e40af; text-decoration: underline;">🔗 Lihat Demo Email (Development)</a></small>';
                }
                $messageType = 'green';
                
            } else {
                // Untuk keamanan, tampilkan pesan yang sama meskipun email tidak ditemukan
                $message = 'Jika email tersebut terdaftar, link reset password akan dikirim ke email Anda.';
                $messageType = 'green';
            }
            
        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            $message = 'Terjadi kesalahan saat memproses permintaan. Silakan coba lagi.';
            $messageType = 'red';
        }
    }
}

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = sanitize($_POST['token']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $message = 'Semua field harus diisi!';
        $messageType = 'red';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password minimal 6 karakter!';
        $messageType = 'red';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Konfirmasi password tidak cocok!';
        $messageType = 'red';
    } else {
        try {
            // Verifikasi ulang token
            $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW() AND status = 'aktif'");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Hash password baru
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password dan hapus token reset
                $updateStmt = $conn->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expires = NULL WHERE id = :id");
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                $message = 'Password berhasil direset! Silakan login dengan password baru Anda.';
                $messageType = 'green';
                $step = 'success';
                
            } else {
                $message = 'Token reset password tidak valid atau sudah kadaluarsa.';
                $messageType = 'red';
                $step = 'request';
            }
            
        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            $message = 'Terjadi kesalahan saat mereset password. Silakan coba lagi.';
            $messageType = 'red';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Rental Mobil</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .form-container {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .btn-primary {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(37, 99, 235, 0.4);
        }
        
        /* Animasi loading */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        
        /* Responsivitas untuk mobile */
        @media (max-width: 640px) {
            .main-container {
                padding: 1rem;
                height: auto;
                min-height: 100vh;
            }
            .form-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100">
    <div class="flex items-center justify-center min-h-screen main-container px-4 py-10">
        <div class="w-full max-w-md form-container rounded-2xl p-8">
            <!-- Header -->
            <div class="mb-6 text-center">
                <a href="<?= BASE_URL ?>" class="flex justify-center items-center mb-4">
                    <i class="fas fa-car-side text-blue-600 text-3xl mr-2"></i>
                    <span class="text-2xl font-bold text-gray-800">Rental Mobil</span>
                </a>
                
                <?php if ($step === 'request'): ?>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Lupa Password?</h2>
                <p class="text-gray-600 text-sm">Masukkan email Anda untuk mendapatkan link reset password</p>
                <?php elseif ($step === 'reset'): ?>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Reset Password</h2>
                <p class="text-gray-600 text-sm">Masukkan password baru untuk akun Anda</p>
                <?php else: ?>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Password Direset</h2>
                <p class="text-gray-600 text-sm">Password Anda telah berhasil direset</p>
                <?php endif; ?>
            </div>
            
            <!-- Flash Message -->
            <?php if (!empty($message)): ?>
            <div class="mb-4 rounded-lg bg-<?= $messageType ?>-100 border border-<?= $messageType ?>-400 text-<?= $messageType ?>-700 px-4 py-3 relative" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-<?= $messageType === 'green' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
                    <span class="block sm:inline"><?= $message ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($step === 'request'): ?>
            <!-- Form Request Reset Password -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-blue-500"></i>
                        </div>
                        <input type="email" id="email" name="email" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan email Anda" required>
                    </div>
                </div>
                
                <div>
                    <button type="submit" name="request_reset" class="btn-primary w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Kirim Link Reset Password
                    </button>
                </div>
            </form>
            
            <?php elseif ($step === 'reset'): ?>
            <!-- Form Reset Password -->
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-blue-500"></i>
                        </div>
                        <input type="password" id="new_password" name="new_password" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Masukkan password baru" required minlength="6">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-blue-500"></i>
                        </div>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input pl-10 w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none bg-white" placeholder="Konfirmasi password baru" required minlength="6">
                    </div>
                </div>
                
                <div>
                    <button type="submit" name="reset_password" class="btn-primary w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">
                        <i class="fas fa-key mr-2"></i>
                        Reset Password
                    </button>
                </div>
            </form>
            
            <?php else: ?>
            <!-- Success State -->
            <div class="text-center space-y-6">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                
                <div>
                    <a href="login.php" class="btn-primary inline-flex items-center bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Login Sekarang
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Kembali ke Login
                </a>
            </div>
            
            <!-- Back to Homepage -->
            <div class="mt-4 border-t border-gray-200 pt-4">
                <div class="flex items-center justify-center">
                    <a href="<?= BASE_URL ?>" class="flex items-center text-gray-600 hover:text-gray-800 font-medium text-sm">
                        <i class="fas fa-home mr-2"></i> 
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const alert = document.querySelector('[role="alert"]');
            if (alert && alert.querySelector('.fa-check-circle')) { // Only auto-hide success messages
                alert.style.transition = 'opacity 1s';
                alert.style.opacity = 0;
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }
        }, 5000);

        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (newPassword && confirmPassword) {
            function validatePassword() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Password tidak cocok');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            newPassword.addEventListener('input', validatePassword);
            confirmPassword.addEventListener('input', validatePassword);
        }
    </script>
</body>
</html> 