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

// Inisialisasi koneksi database
$database = new Database();
$conn = $database->getConnection();

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