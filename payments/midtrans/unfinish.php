<?php
/**
 * File untuk menangani redirect setelah pembayaran tidak selesai
 */
require_once __DIR__ . '/../../user/includes/header.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Anda harus login terlebih dahulu";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "login.php");
    exit;
}

// Ambil parameter redirect
$order_id = $_GET['order_id'] ?? '';

// Jika tidak ada order_id, redirect ke halaman pemesanan
if (empty($order_id)) {
    $_SESSION['flash_message'] = "Pembayaran tidak selesai";
    $_SESSION['flash_type'] = "yellow";
    header("Location: " . USER_URL . "pesanan.php");
    exit;
}

// Siapkan pesan flash
$_SESSION['flash_message'] = "Pembayaran belum diselesaikan. Silakan coba lagi atau pilih metode pembayaran lain.";
$_SESSION['flash_type'] = "yellow";

// Redirect ke halaman detail pemesanan
header("Location: " . USER_URL . "pemesanan_detail.php?kode=" . $order_id);
exit; 