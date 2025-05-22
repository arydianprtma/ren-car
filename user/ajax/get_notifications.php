<?php
require_once '../../config/config.php';
require_once '../../classes/Notification.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'notifications' => []
    ]);
    exit;
}

try {
    // Inisialisasi koneksi database
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Koneksi database gagal");
    }
    
    // Parameter
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $userId = $_SESSION['user_id'];
    
    // Inisialisasi class Notification
    $notification = new Notification($conn);
    
    // Ambil notifikasi
    $notifications = $notification->getUserNotifications($userId, $limit, $offset);
    
    // Response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => count($notifications),
        'notifications' => $notifications
    ]);
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memuat notifikasi',
        'error' => $e->getMessage(),
        'notifications' => []
    ]);
} 