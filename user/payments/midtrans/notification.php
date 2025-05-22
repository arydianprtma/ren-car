<?php
/**
 * File notification handler untuk menerima notifikasi dari server Midtrans
 * File ini dipanggil langsung oleh server Midtrans, bukan dari browser user
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/midtrans/config.php';

// Untuk mencatat notifikasi midtrans dalam file log
function writeLog($message) {
    $logFile = __DIR__ . '/../../../logs/midtrans_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, $timestamp . ' ' . $message . PHP_EOL, FILE_APPEND);
}

// Ambil data JSON yang dikirim oleh Midtrans
$notificationJson = file_get_contents('php://input');
writeLog("Received notification: " . $notificationJson);

// Decode JSON
$notification = json_decode($notificationJson, true);

if (!is_array($notification) || empty($notification)) {
    http_response_code(400);
    writeLog("Invalid notification data");
    exit;
}

// Ambil data dari notifikasi
$orderId = $notification['order_id'] ?? '';
$statusCode = $notification['status_code'] ?? '';
$transactionStatus = $notification['transaction_status'] ?? '';
$fraudStatus = $notification['fraud_status'] ?? '';
$grossAmount = $notification['gross_amount'] ?? 0;
$signature = $notification['signature_key'] ?? '';

// Validasi signature
$mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . MIDTRANS_SERVER_KEY);
if ($signature !== $mySignature) {
    http_response_code(403);
    writeLog("Invalid signature for order: " . $orderId);
    exit;
}

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

try {
    // Mulai transaksi
    $conn->beginTransaction();
    
    // Cek apakah order ID valid dan ada di database
    $stmt = $conn->prepare("SELECT p.id, p.kode_pemesanan, p.user_id, p.status_pemesanan 
                            FROM pemesanan p 
                            WHERE p.midtrans_order_id = ? OR p.kode_pemesanan = ?");
    $stmt->execute([$orderId, $orderId]);
    $pemesanan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pemesanan) {
        http_response_code(404);
        writeLog("Order not found: " . $orderId);
        $conn->rollback();
        exit;
    }
    
    // Pemetaan status Midtrans ke status pemesanan
    $mappedStatus = $transactionStatus;
    $statusPemesanan = 'menunggu';
    
    switch ($transactionStatus) {
        case 'capture':
        case 'settlement':
            $statusPemesanan = 'dikonfirmasi';
            break;
        case 'pending':
            $statusPemesanan = 'menunggu';
            break;
        case 'deny':
        case 'cancel':
        case 'expire':
        case 'failure':
            $statusPemesanan = 'dibatalkan';
            break;
    }
    
    // Update status pemesanan
    $stmt = $conn->prepare("UPDATE pemesanan SET 
                          status_pemesanan = :status,
                          metode_pembayaran = 'midtrans',
                          midtrans_status = :midtrans_status,
                          updated_at = NOW()
                          WHERE kode_pemesanan = :kode_pemesanan");
                          
    $stmt->bindParam(':status', $statusPemesanan, PDO::PARAM_STR);
    $stmt->bindParam(':midtrans_status', $mappedStatus, PDO::PARAM_STR);
    $stmt->bindParam(':kode_pemesanan', $pemesanan['kode_pemesanan'], PDO::PARAM_STR);
    $stmt->execute();
    
    // Jika status dibatalkan, update status mobil menjadi tersedia
    if ($statusPemesanan == 'dibatalkan') {
        $stmt = $conn->prepare("UPDATE mobil m 
                                JOIN pemesanan p ON m.id = p.mobil_id
                                SET m.status = 'tersedia'
                                WHERE p.kode_pemesanan = ?");
        $stmt->execute([$pemesanan['kode_pemesanan']]);
    }
    
    // Commit transaksi
    $conn->commit();
    
    // Simpan detail notifikasi
    $stmt = $conn->prepare("INSERT INTO midtrans_notification 
                          (order_id, status_code, transaction_status, fraud_status, gross_amount, payment_type, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $paymentType = $notification['payment_type'] ?? 'unknown';
    $stmt->execute([$orderId, $statusCode, $transactionStatus, $fraudStatus, $grossAmount, $paymentType]);
    
    http_response_code(200);
    writeLog("Successfully processed notification for order: " . $orderId . " with status: " . $transactionStatus);
    
} catch (PDOException $e) {
    $conn->rollback();
    http_response_code(500);
    writeLog("Error processing notification: " . $e->getMessage() . " for order: " . $orderId);
}
?> 