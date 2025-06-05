<?php
/**
 * Main Configuration File
 */

// Aktifkan output buffering untuk mencegah 'headers already sent' error
if (ob_get_level() == 0) ob_start();

// Database Configuration
require_once 'database.php';

// Base URL Configuration
// Untuk akses lokal (komputer sendiri)
define('BASE_URL', 'http://localhost/Rental%20Mobil/');

// Untuk akses melalui jaringan lokal (LAN/WiFi yang sama)
// define('BASE_URL', 'http://192.168.18.116/Rental%20Mobil/');

// Untuk akses melalui internet (ganti dengan IP publik atau domain)
// Contoh jika menggunakan ngrok, cloudflare tunnel, atau layanan sejenis
// define('BASE_URL', 'https://rentalmobil-nama-anda.ngrok.io/');
// define('BASE_URL', 'https://rentalmobil.domain-anda.com/');

// Atau dengan domain yang sudah terdaftar
// define('BASE_URL', 'https://rentalmobil.com/');

define('ADMIN_URL', BASE_URL . 'admin/');
define('USER_URL', BASE_URL . 'user/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Load Google Configuration if exists
if (file_exists(__DIR__ . '/google_config.php')) {
    require_once 'google_config.php';
}

// Session Configuration
session_start();

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set error handler untuk menangani exception dan error dengan lebih baik
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo "<h1>Terjadi Kesalahan</h1>";
    echo "<p>Maaf, terjadi kesalahan pada sistem. Silahkan coba beberapa saat lagi.</p>";
    if (ini_get('display_errors')) {
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    exit;
});

// Set error handler untuk error fatal
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo "<h1>Terjadi Kesalahan</h1>";
        echo "<p>Maaf, terjadi kesalahan pada sistem. Silahkan coba beberapa saat lagi.</p>";
        if (ini_get('display_errors')) {
            echo "<p>Error: " . htmlspecialchars($error['message']) . "</p>";
        }
    }
});

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Function to redirect
function redirect($url) {
    // Jika headers sudah dikirim, coba gunakan JavaScript
    if (headers_sent()) {
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
        exit;
    }
    
    // Jika output buffering aktif, bersihkan buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Lakukan redirect normal
    header("Location: $url");
    exit();
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Remember Me Functions
function setRememberToken($userId, $token) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $expires = date('Y-m-d H:i:s', strtotime('+30 days')); // Token berlaku 30 hari
        
        $stmt = $conn->prepare("UPDATE users SET remember_token = :token, remember_token_expires = :expires WHERE id = :user_id");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error setting remember token: " . $e->getMessage());
        return false;
    }
}

function clearRememberToken($userId) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error clearing remember token: " . $e->getMessage());
        return false;
    }
}

function verifyRememberToken($token) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = :token AND remember_token_expires > NOW() AND status = 'aktif'");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    } catch (Exception $e) {
        error_log("Error verifying remember token: " . $e->getMessage());
        return false;
    }
}

function autoLoginFromRememberToken() {
    // Cek apakah sudah login
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Cek apakah ada remember token di cookie
    if (!isset($_COOKIE['user_remember'])) {
        return false;
    }
    
    $token = $_COOKIE['user_remember'];
    $user = verifyRememberToken($token);
    
    if ($user) {
        // Set session untuk auto login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_email'] = $user['email'];
        
        // Generate token baru untuk keamanan
        $newToken = bin2hex(random_bytes(32));
        setRememberToken($user['id'], $newToken);
        setcookie('user_remember', $newToken, time() + (86400 * 30), '/'); // 30 hari
        
        return true;
    } else {
        // Token tidak valid, hapus cookie
        setcookie('user_remember', '', time() - 3600, '/');
        return false;
    }
}

// Check if user is logged in (with auto-login from remember token)
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Coba auto login dari remember token
    return autoLoginFromRememberToken();
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Function to generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Function to set flash message
function setFlashMessage($message, $type = 'green') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
} 