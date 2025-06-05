<?php
/**
 * Demo halaman untuk melihat email reset password yang akan dikirim
 * Untuk development/testing - hapus di production
 */
require_once '../config/config.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect(USER_URL);
}

$db = new Database();
$conn = $db->getConnection();

// Ambil token dari URL
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
$userEmail = isset($_GET['email']) ? sanitize($_GET['email']) : '';

if (empty($token) || empty($userEmail)) {
    redirect('lupa-password.php');
}

// Cek apakah token valid
$stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND reset_token = :token AND reset_token_expires > NOW()");
$stmt->bindParam(':email', $userEmail);
$stmt->bindParam(':token', $token);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    redirect('lupa-password.php');
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);
$resetUrl = BASE_URL . 'user/lupa-password.php?token=' . $token;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Email Reset Password - Rental Mobil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; }
        .email-preview {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .email-header {
            background: #1e40af;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .email-content {
            padding: 30px;
            line-height: 1.6;
        }
        .btn-reset {
            background: #1e40af;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            margin: 20px 0;
        }
        .btn-reset:hover {
            background: #1e3a8a;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-envelope text-blue-600 mr-2"></i>
                Demo Email Reset Password
            </h1>
            <p class="text-gray-600">Ini adalah preview email yang akan dikirim ke pengguna</p>
            <p class="text-sm text-red-600 mt-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                File demo ini hanya untuk development - hapus di production!
            </p>
        </div>

        <!-- Email Preview -->
        <div class="email-preview">
            <!-- Email Header -->
            <div class="email-header">
                <h2>
                    <i class="fas fa-car-side mr-2"></i>
                    Rental Mobil
                </h2>
                <p class="mt-2 text-blue-100">Reset Password Anda</p>
            </div>
            
            <!-- Email Content -->
            <div class="email-content">
                <h3 style="color: #1e40af; margin-bottom: 20px;">Halo <?= htmlspecialchars($user['nama']) ?>!</h3>
                
                <p>Anda telah meminta reset password untuk akun Rental Mobil Anda.</p>
                
                <p>Klik tombol di bawah ini untuk mereset password Anda:</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="<?= $resetUrl ?>" class="btn-reset">
                        <i class="fas fa-key mr-2"></i>
                        Reset Password Saya
                    </a>
                </div>
                
                <p style="font-size: 14px; color: #666;">
                    Atau copy dan paste link berikut ke browser Anda:<br>
                    <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 8px; word-break: break-all;">
                        <?= $resetUrl ?>
                    </code>
                </p>
                
                <div style="background: #fef3cd; border: 1px solid #fbbf24; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; color: #92400e;">
                        <i class="fas fa-clock mr-2"></i>
                        <strong>Penting:</strong> Link ini akan kadaluarsa dalam 1 jam.
                    </p>
                </div>
                
                <p style="color: #666; font-size: 14px;">
                    Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tidak akan berubah.
                </p>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
                
                <p style="color: #666; font-size: 14px;">
                    Terima kasih,<br>
                    <strong>Tim Rental Mobil</strong>
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-8 space-x-4">
            <a href="<?= $resetUrl ?>" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                <i class="fas fa-key mr-2"></i>
                Test Reset Password
            </a>
            
            <a href="lupa-password.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Form
            </a>
        </div>

        <!-- Info Panel -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                Informasi Reset Password
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?>
                </div>
                <div>
                    <strong>Nama:</strong> <?= htmlspecialchars($user['nama']) ?>
                </div>
                <div>
                    <strong>Token:</strong> <code class="bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars(substr($token, 0, 16)) ?>...</code>
                </div>
                <div>
                    <strong>Kadaluarsa:</strong> <?= date('d/m/Y H:i:s', strtotime($user['reset_token_expires'])) ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 