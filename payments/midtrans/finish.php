<?php
/**
 * File untuk menangani redirect setelah pembayaran selesai
 */
require_once __DIR__ . '/../../user/includes/header.php';
require_once __DIR__ . '/Midtrans.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Anda harus login terlebih dahulu";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "login.php");
    exit;
}

// Ambil parameter
$order_id = $_GET['order_id'] ?? '';
$status = $_GET['status'] ?? '';

// Jika order_id tidak ada, redirect ke halaman pesanan
if (empty($order_id)) {
    $_SESSION['flash_message'] = "Parameter pesanan tidak valid";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "pesanan.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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
    
    // Inisialisasi Midtrans
    $midtrans = new Midtrans();

    // Cek status pembayaran dari Midtrans API
    $transaction = $midtrans->getStatus($order_id);

    // Jika ada error saat mengambil status, cek dari parameter URL saja
    if (isset($transaction['status']) && $transaction['status'] === 'error') {
        $transaction_status = ($status === 'success') ? 'settlement' : (($status === 'pending') ? 'pending' : 'error');
    } else {
        $transaction_status = $transaction['transaction_status'] ?? '';
    }

    // Tentukan status pemesanan dan pesan yang sesuai
    $mapped_status = $midtrans->getMappedOrderStatus($transaction_status);
    $statusText = '';
    $statusClass = '';

    switch ($mapped_status) {
        case 'dikonfirmasi':
            $statusText = "Pembayaran berhasil dikonfirmasi";
            $statusClass = "green";
            break;
        case 'menunggu':
            $statusText = "Pembayaran sedang diproses";
            $statusClass = "yellow";
            break;
        case 'dibatalkan':
            $statusText = "Pembayaran gagal atau dibatalkan";
            $statusClass = "red";
            break;
        default:
            $statusText = "Status pembayaran tidak diketahui";
            $statusClass = "gray";
    }

    // Siapkan pesan flash
    $_SESSION['flash_message'] = $statusText;
    $_SESSION['flash_type'] = $statusClass;

    // Redirect ke halaman detail pemesanan
    header("Location: " . USER_URL . "pemesanan_detail.php?kode=" . $kode_pemesanan);
    exit;
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan saat mengambil data pemesanan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "pesanan.php");
    exit;
} 