<?php
/**
 * File untuk menangani redirect setelah terjadi error pembayaran
 */
// Load config dan database langsung dari root
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Anda harus login terlebih dahulu";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "login.php");
    exit;
}

// Ambil parameter
$order_id = $_GET['order_id'] ?? '';

// Jika order_id tidak ada, redirect ke halaman pesanan
if (empty($order_id)) {
    $_SESSION['flash_message'] = "Parameter pesanan tidak valid";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "pesanan.php");
    exit;
}

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil data pemesanan
try {
    // Cek apakah ini order_id Midtrans (dengan format kode_pemesanan-timestamp)
    if (strpos($order_id, '-') !== false) {
        // Ini adalah order_id dari Midtrans
        $stmt = $conn->prepare("SELECT * FROM pemesanan WHERE midtrans_order_id = ?");
        $stmt->execute([$order_id]);
    } else {
        // Ini adalah kode_pemesanan biasa
        $stmt = $conn->prepare("SELECT * FROM pemesanan WHERE kode_pemesanan = ?");
        $stmt->execute([$order_id]);
    }
    
    $pemesanan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika pemesanan tidak ditemukan, redirect ke halaman pesanan
    if (!$pemesanan) {
        $_SESSION['flash_message'] = "Pemesanan tidak ditemukan";
        $_SESSION['flash_type'] = "red";
        header("Location: " . USER_URL . "pesanan.php");
        exit;
    }
    
    // Dapatkan kode_pemesanan (untuk redirect)
    $kode_pemesanan = $pemesanan['kode_pemesanan'];
    
    // Siapkan pesan flash
    $_SESSION['flash_message'] = "Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.";
    $_SESSION['flash_type'] = "red";

    // Redirect ke halaman detail pemesanan
    header("Location: " . USER_URL . "pemesanan_detail.php?kode=" . $kode_pemesanan);
    exit;
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan saat mengambil data pemesanan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "pesanan.php");
    exit;
} 