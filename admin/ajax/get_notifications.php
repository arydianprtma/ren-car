<?php
// Aktifkan output buffering untuk mencegah 'headers already sent' error
if (ob_get_level() == 0) ob_start();

error_log("Loading admin/ajax/get_notifications.php");

// Pastikan config dan class Notification di-load
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Notification.php';

error_log("Files loaded successfully in get_notifications.php");

// Cek apakah admin sudah login
if (!isAdminLoggedIn()) {
    error_log("Admin not logged in");
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
    
    error_log("Database connection successful in get_notifications.php");
    
    // Parameter
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $userId = $_SESSION['admin_id'];
    $onlyUnread = isset($_GET['unread']) && $_GET['unread'] === 'true';
    
    error_log("Creating Notification instance with userId: {$userId}");
    
    // Inisialisasi class Notification
    $notification = new Notification($conn);
    
    error_log("Notification class initialized successfully");
    
    // Ambil notifikasi
    $notifications = $notification->getUserNotifications($userId, $limit, $offset, $onlyUnread);
    
    // Hitung jumlah notifikasi yang belum dibaca (untuk badge)
    $unreadCount = $notification->countUnreadNotifications($userId);
    
    // Menghitung jumlah notifikasi berdasarkan kategori
    $categoryCounts = [
        'user_baru' => 0,
        'pesanan_baru' => 0,
        'pembayaran' => 0,
        'pengembalian' => 0,
        'sistem' => 0
    ];
    
    // Query untuk menghitung jumlah notifikasi per kategori
    $stmt = $conn->prepare("
        SELECT tipe, COUNT(*) as count 
        FROM notifikasi 
        WHERE user_id = :user_id AND status = 'belum_dibaca'
        GROUP BY tipe
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Isi array categoryCounts dengan hasil query
    foreach ($results as $row) {
        if (isset($categoryCounts[$row['tipe']])) {
            $categoryCounts[$row['tipe']] = (int)$row['count'];
        }
    }
    
    error_log("Retrieved " . count($notifications) . " notifications, " . $unreadCount . " unread");
    
    // Response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $unreadCount,
        'notifications' => $notifications,
        'categories' => $categoryCounts
    ]);
} catch (Exception $e) {
    error_log("Admin get notifications error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memuat notifikasi',
        'error' => $e->getMessage(),
        'notifications' => []
    ]);
} 