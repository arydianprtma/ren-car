<?php
/**
 * File untuk mengecek autentikasi admin
 * File ini akan di-include di setiap halaman admin yang membutuhkan autentikasi
 */

// Aktifkan output buffering jika belum aktif
if (ob_get_level() == 0) ob_start();

// Load config jika belum di-load
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Cek apakah admin sudah login
if (!isAdminLoggedIn()) {
    // Jika belum login, redirect ke halaman login
    redirect(ADMIN_URL . 'login.php');
}

// Ambil data admin yang sedang login
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_username = $_SESSION['admin_username'] ?? null;
$admin_nama = $_SESSION['admin_nama'] ?? null;
$admin_email = $_SESSION['admin_email'] ?? null; 