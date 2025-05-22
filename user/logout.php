<?php
require_once '../config/config.php';

// Hapus session
session_unset();
session_destroy();

// Hapus cookie
if (isset($_COOKIE['user_remember'])) {
    setcookie('user_remember', '', time() - 3600, '/');
}

// Redirect ke halaman beranda
redirect(USER_URL); 