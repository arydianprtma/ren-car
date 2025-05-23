<?php
/**
 * Halaman Notifikasi Admin
 */

// Aktifkan output buffering untuk mencegah 'headers already sent' error
if (ob_get_level() == 0) ob_start();

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Loading admin/notifications.php");

// Include auth check
require_once 'includes/auth_check.php';

// Include class yang diperlukan dengan absolute path untuk memastikan
$basePath = dirname(dirname(__FILE__));
$notificationClassPath = $basePath . '/classes/Notification.php';
$databaseClassPath = $basePath . '/config/database.php';

error_log("Base path: " . $basePath);
error_log("Notification class path: " . $notificationClassPath);
error_log("Database class path: " . $databaseClassPath);

// Periksa jika file exist
if (!file_exists($notificationClassPath)) {
    error_log("ERROR: Notification class file not found at: " . $notificationClassPath);
} else {
    error_log("Notification class file exists at: " . $notificationClassPath);
}

require_once $databaseClassPath;

// Periksa jika class Notification belum di-load
if (!class_exists('Notification')) {
    require_once $notificationClassPath;
    error_log("Loaded Notification class from absolute path");
}

error_log("Classes loaded successfully in notifications.php");

try {
    // Inisialisasi koneksi ke database
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Koneksi database gagal");
    }
    
    error_log("Database connection successful in notifications.php");
    
    // Inisialisasi Notification class
    $notif = new Notification($conn);
    
    error_log("Notification class initialized successfully in notifications.php");
    
    // Ambil notifikasi admin
    $userId = $_SESSION['admin_id'] ?? 0;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $category = isset($_GET['kategori']) ? $_GET['kategori'] : 'all';
    
    // Action untuk menandai notifikasi sebagai dibaca
    if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
        $notificationId = (int)$_GET['id'];
        $notif->markAsRead($notificationId, $userId);
        setFlashMessage('Notifikasi ditandai sebagai dibaca', 'green');
        redirect(ADMIN_URL . 'notifications.php');
        exit; // Tidak akan pernah dijalankan karena redirect() sudah exit
    }
    
    // Action untuk menandai semua notifikasi sebagai dibaca
    if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
        $notif->markAllAsRead($userId);
        setFlashMessage('Semua notifikasi ditandai sebagai dibaca', 'green');
        redirect(ADMIN_URL . 'notifications.php');
        exit; // Tidak akan pernah dijalankan karena redirect() sudah exit
    }
    
    // Ambil notifikasi
    $notifications = $notif->getUserNotifications($userId, $limit, $offset);
    
    // Filter notifikasi berdasarkan kategori jika bukan 'all'
    if ($category !== 'all') {
        $filtered = [];
        foreach ($notifications as $notification) {
            if ($notification['tipe'] === $category) {
                $filtered[] = $notification;
            }
        }
        $notifications = $filtered;
    }
    
    // Hitung total notifikasi untuk pagination (termasuk filter)
    if ($category === 'all') {
        $totalNotifications = $notif->getTotalUserNotifications($userId);
    } else {
        $totalNotifications = count($notifications); // Untuk filter gunakan hasil count
    }
    $totalPages = ceil($totalNotifications / $limit);
    
    error_log("All notification data loaded successfully in notifications.php");
    
    // Sekarang kita bisa memasukkan header.php karena semua redirect sudah dilakukan
    require_once 'includes/header.php';
    
    // Tampilkan notifikasi
    ?>
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Notifikasi</h1>
            
            <?php if (!empty($notifications)): ?>
            <a href="<?= ADMIN_URL ?>notifications.php?action=mark_all_read" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                <i class="fas fa-check-double mr-2"></i> Tandai Semua Dibaca
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Filter Kategori -->
        <div class="mb-4 overflow-x-auto">
            <div class="inline-flex rounded-md shadow-sm">
                <a href="<?= ADMIN_URL ?>notifications.php" class="px-4 py-2 text-sm font-medium <?= $category === 'all' ? 'bg-blue-50 text-blue-600 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?> border rounded-l-lg focus:z-10 focus:ring-2 focus:ring-blue-500">
                    Semua
                </a>
                <a href="<?= ADMIN_URL ?>notifications.php?kategori=user_baru" class="px-4 py-2 text-sm font-medium <?= $category === 'user_baru' ? 'bg-blue-50 text-blue-600 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?> border-t border-b border-r focus:z-10 focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-user-plus mr-1 text-indigo-500"></i> User Baru
                </a>
                <a href="<?= ADMIN_URL ?>notifications.php?kategori=pesanan_baru" class="px-4 py-2 text-sm font-medium <?= $category === 'pesanan_baru' ? 'bg-blue-50 text-blue-600 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?> border-t border-b border-r focus:z-10 focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-shopping-cart mr-1 text-green-500"></i> Pemesanan
                </a>
                <a href="<?= ADMIN_URL ?>notifications.php?kategori=pembayaran" class="px-4 py-2 text-sm font-medium <?= $category === 'pembayaran' ? 'bg-blue-50 text-blue-600 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?> border-t border-b border-r focus:z-10 focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-credit-card mr-1 text-purple-500"></i> Pembayaran
                </a>
                <a href="<?= ADMIN_URL ?>notifications.php?kategori=pengembalian" class="px-4 py-2 text-sm font-medium <?= $category === 'pengembalian' ? 'bg-blue-50 text-blue-600 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ?> border-t border-b border-r rounded-r-lg focus:z-10 focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-undo mr-1 text-orange-500"></i> Pengembalian
                </a>
            </div>
        </div>
        
        <?php if (empty($notifications)): ?>
        <div class="flex flex-col items-center justify-center py-12">
            <div class="text-6xl text-gray-300 mb-4">
                <i class="fas fa-bell-slash"></i>
            </div>
            <h3 class="text-xl font-medium text-gray-700 mb-2">Tidak Ada Notifikasi</h3>
            <p class="text-gray-500 text-center">Belum ada notifikasi untuk Anda saat ini.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-200">
            <?php foreach ($notifications as $notification): ?>
            <div class="py-4 px-2 <?= $notification['status'] === 'belum_dibaca' ? 'bg-blue-50' : '' ?> rounded-lg mb-2 transition-all hover:bg-gray-50">
                <div class="flex flex-col sm:flex-row items-start">
                    <div class="mr-4 mb-3 sm:mb-0 sm:mt-1">
                        <?php 
                        $iconClass = 'text-blue-500';
                        $icon = 'bell';
                        
                        // Tentukan icon berdasarkan tipe notifikasi
                        switch ($notification['tipe']) {
                            case 'pesanan_baru':
                                $icon = 'shopping-cart';
                                $iconClass = 'text-green-500';
                                break;
                            case 'pembayaran':
                                $icon = 'credit-card';
                                $iconClass = 'text-purple-500';
                                break;
                            case 'pengembalian':
                                $icon = 'undo';
                                $iconClass = 'text-orange-500';
                                break;
                            case 'user_baru':
                                $icon = 'user-plus';
                                $iconClass = 'text-indigo-500';
                                break;
                            case 'sistem':
                                $icon = 'cog';
                                $iconClass = 'text-gray-500';
                                break;
                        }
                        ?>
                        <div class="w-10 h-10 rounded-full bg-<?= substr($iconClass, 5, strpos($iconClass, '-', 5) - 5) ?>-100 flex items-center justify-center">
                            <i class="fas fa-<?= $icon ?> <?= $iconClass ?>"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                            <h4 class="text-md font-semibold text-gray-800 mb-1"><?= htmlspecialchars($notification['judul']) ?></h4>
                            <div class="flex items-center mb-2 sm:mb-0">
                                <span class="text-xs text-gray-500">
                                    <?= date('d M Y, H:i', strtotime($notification['created_at'])) ?>
                                </span>
                                
                                <?php if ($notification['status'] === 'belum_dibaca'): ?>
                                <a href="<?= ADMIN_URL ?>notifications.php?action=mark_read&id=<?= $notification['id'] ?>" class="ml-4 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-gray-600"><?= htmlspecialchars($notification['pesan']) ?></p>
                        
                        <?php if ($notification['referensi_id'] && $notification['referensi_tabel']): ?>
                        <div class="mt-2">
                            <?php 
                            $linkText = "";
                            $linkUrl = "#";
                            
                            switch ($notification['referensi_tabel']) {
                                case 'pemesanan':
                                    $linkText = "Lihat Pemesanan #" . $notification['referensi_id'];
                                    $linkUrl = ADMIN_URL . "pemesanan/detail.php?id=" . $notification['referensi_id'];
                                    break;
                                case 'users':
                                    $linkText = "Lihat User";
                                    $linkUrl = ADMIN_URL . "user/detail.php?id=" . $notification['referensi_id'];
                                    break;
                                case 'mobil':
                                    $linkText = "Lihat Mobil";
                                    $linkUrl = ADMIN_URL . "mobil/detail.php?id=" . $notification['referensi_id'];
                                    break;
                            }
                            
                            if ($linkText && $linkUrl != "#"):
                            ?>
                            <a href="<?= $linkUrl ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center inline-block mt-1">
                                <?= $linkText ?> <i class="fas fa-chevron-right ml-1 text-xs"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="inline-flex rounded-md shadow-sm">
                <?php if ($page > 1): ?>
                <a href="<?= ADMIN_URL ?>notifications.php?page=<?= $page - 1 ?><?= $category !== 'all' ? '&kategori='.$category : '' ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php else: ?>
                <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-l-md">
                    <i class="fas fa-chevron-left"></i>
                </span>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                <span class="px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-gray-300">
                    <?= $i ?>
                </span>
                <?php else: ?>
                <a href="<?= ADMIN_URL ?>notifications.php?page=<?= $i ?><?= $category !== 'all' ? '&kategori='.$category : '' ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                    <?= $i ?>
                </a>
                <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="<?= ADMIN_URL ?>notifications.php?page=<?= $page + 1 ?><?= $category !== 'all' ? '&kategori='.$category : '' ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-r-md">
                    <i class="fas fa-chevron-right"></i>
                </span>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    
    require_once 'includes/footer.php';
} catch (Exception $e) {
    error_log("Error in notifications.php: " . $e->getMessage());
    
    // Jika header belum dikirim, kita bisa redirect ke halaman error
    if (!headers_sent()) {
        setFlashMessage('Terjadi kesalahan: ' . $e->getMessage(), 'red');
        redirect(ADMIN_URL . 'index.php');
        exit;
    }
    
    // Jika header sudah dikirim, tampilkan pesan error
    echo '<div style="padding: 20px; background-color: #f44336; color: white; margin-bottom: 15px;">';
    echo '<h2>Terjadi Kesalahan</h2>';
    echo '<p>Maaf, terjadi kesalahan pada sistem. Silahkan coba beberapa saat lagi.</p>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    exit;
}
?> 