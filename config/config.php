<?php
/**
 * Main Configuration File
 */

// Aktifkan output buffering untuk mencegah 'headers already sent' error
if (ob_get_level() == 0) ob_start();

// Database Configuration
require_once 'database.php';

// Base URL Configuration
define('BASE_URL', 'http://localhost/Rental%20Mobil/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('USER_URL', BASE_URL . 'user/');
define('ASSETS_URL', BASE_URL . 'assets/');

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

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
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