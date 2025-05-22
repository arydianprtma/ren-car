<?php
/**
 * File untuk menangani notifikasi pembayaran dari Midtrans
 * 
 * Midtrans akan mengirimkan notifikasi ke URL ini ketika status pembayaran berubah
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Midtrans.php';

// Ambil JSON input dari Midtrans
$json_result = file_get_contents('php://input');
$notification = json_decode($json_result, true);

// Log notifikasi yang diterima
error_log("Midtrans Notification: " . $json_result);

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Inisialisasi Midtrans
$midtrans = new Midtrans();

// Pastikan notifikasi valid
if (!$notification) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid notification']);
    exit;
}

// Ambil data dari notifikasi
$transaction_status = $notification['transaction_status'] ?? '';
$fraud_status = $notification['fraud_status'] ?? '';
$order_id = $notification['order_id'] ?? '';
$gross_amount = $notification['gross_amount'] ?? 0;

// Verifikasi signature jika tersedia
if (isset($notification['signature_key'])) {
    $signature = $notification['signature_key'];
    if (!$midtrans->verifyNotificationSignature($order_id, $transaction_status, $gross_amount, $signature)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }
}

// Periksa apakah order_id valid
if (empty($order_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit;
}

// Verifikasi status transaksi dari Midtrans API
$transaction = $midtrans->getStatus($order_id);
if (isset($transaction['status']) && $transaction['status'] === 'error') {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $transaction['message']]);
    exit;
}

// Tentukan status pemesanan berdasarkan status transaksi
$status_pemesanan = $midtrans->getMappedOrderStatus($transaction_status);

// Tambahkan metode pembayaran
$payment_type = $notification['payment_type'] ?? '';
$midtrans_va_numbers = $notification['va_numbers'] ?? null;
$midtrans_bank = '';

if ($payment_type === 'bank_transfer' && $midtrans_va_numbers) {
    $midtrans_bank = $midtrans_va_numbers[0]['bank'] ?? '';
}

// Tentukan metode pembayaran berdasarkan payment_type dari Midtrans
$metode_pembayaran = 'e-wallet'; // Default
if ($payment_type === 'bank_transfer') {
    $metode_pembayaran = 'transfer_bank';
} elseif ($payment_type === 'cstore') {
    $metode_pembayaran = 'tunai'; // Alfamart/Indomaret masuk kategori tunai
}

// Persiapkan data tambahan midtrans
$midtrans_data = [
    'midtrans_id' => $notification['transaction_id'] ?? '',
    'midtrans_status' => $transaction_status,
    'midtrans_payment_type' => $payment_type,
    'midtrans_bank' => $midtrans_bank
];

// Update status pemesanan
try {
    // Mulai transaksi database
    $conn->beginTransaction();
    
    // Periksa terlebih dahulu apakah order_id ada di database
    // Karena sekarang order_id berformat kode_pemesanan-timestamp, cari berdasarkan midtrans_order_id
    $check_stmt = $conn->prepare("SELECT id, kode_pemesanan, status_pemesanan FROM pemesanan WHERE midtrans_order_id = ?");
    $check_stmt->execute([$order_id]);
    $pemesanan = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pemesanan) {
        throw new Exception("Order ID not found: " . $order_id);
    }
    
    // Jangan ubah status jika status saat ini sudah final (selesai atau dibatalkan)
    if ($pemesanan['status_pemesanan'] === 'selesai' || $pemesanan['status_pemesanan'] === 'dibatalkan') {
        // Hanya log notifikasi dan tidak lakukan update
        error_log("Order ID {$pemesanan['kode_pemesanan']} already has final status: {$pemesanan['status_pemesanan']}");
    } else {
        // Update status pemesanan
        $update_stmt = $conn->prepare("UPDATE pemesanan SET 
                                    status_pemesanan = :status,
                                    metode_pembayaran = :metode,
                                    midtrans_id = :midtrans_id,
                                    midtrans_status = :midtrans_status,
                                    midtrans_payment_type = :midtrans_payment_type,
                                    midtrans_bank = :midtrans_bank,
                                    updated_at = NOW()
                                    WHERE midtrans_order_id = :midtrans_order_id");
                                    
        $update_stmt->bindParam(':status', $status_pemesanan, PDO::PARAM_STR);
        $update_stmt->bindParam(':metode', $metode_pembayaran, PDO::PARAM_STR);
        $update_stmt->bindParam(':midtrans_id', $midtrans_data['midtrans_id'], PDO::PARAM_STR);
        $update_stmt->bindParam(':midtrans_status', $midtrans_data['midtrans_status'], PDO::PARAM_STR);
        $update_stmt->bindParam(':midtrans_payment_type', $midtrans_data['midtrans_payment_type'], PDO::PARAM_STR);
        $update_stmt->bindParam(':midtrans_bank', $midtrans_data['midtrans_bank'], PDO::PARAM_STR);
        $update_stmt->bindParam(':midtrans_order_id', $order_id, PDO::PARAM_STR);
        $update_stmt->execute();
    }
    
    // Commit transaksi
    $conn->commit();
    
    // Respons sukses
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Notification processed successfully']);
    
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollback();
    
    // Log error
    error_log("Error processing Midtrans notification: " . $e->getMessage());
    
    // Respons error
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 