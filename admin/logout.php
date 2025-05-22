<?php
require_once '../config/config.php';

// Hapus session
session_unset();
session_destroy();

// Hapus cookie
if (isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, '/');
}

// Redirect ke halaman login
redirect(ADMIN_URL . 'login.php'); 