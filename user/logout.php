<?php
require_once '../config/config.php';

// Hapus remember token dari database jika ada
if (isset($_SESSION['user_id'])) {
    clearRememberToken($_SESSION['user_id']);
}

// Hapus session
session_unset();
session_destroy();

// Hapus cookie remember me
if (isset($_COOKIE['user_remember'])) {
    setcookie('user_remember', '', time() - 3600, '/');
}

// Redirect ke halaman beranda
redirect(USER_URL); 