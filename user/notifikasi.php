<?php
require_once '../config/config.php';
require_once '../classes/Notification.php';

// Cek apakah user sudah login, jika belum redirect ke halaman login
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Silakan login terlebih dahulu.";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "login.php");
    exit;
}

// Inisialisasi koneksi database
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Koneksi database gagal");
    }
    
    $userId = $_SESSION['user_id'];
    $notification = new Notification($conn);
    
    // Menandai semua sebagai sudah dibaca
    if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
        $notification->markAllAsRead($userId);
        $_SESSION['flash_message'] = "Semua notifikasi telah ditandai sebagai dibaca.";
        $_SESSION['flash_type'] = "green";
        header("Location: " . USER_URL . "notifikasi.php");
        exit;
    }
    
    // Menandai satu notifikasi sebagai sudah dibaca
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $notificationId = (int)$_GET['id'];
        $notification->markAsRead($notificationId, $userId);
    }
    
    // Hapus notifikasi
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $notificationId = (int)$_GET['delete'];
        if ($notification->deleteNotification($notificationId, $userId)) {
            $_SESSION['flash_message'] = "Notifikasi berhasil dihapus.";
            $_SESSION['flash_type'] = "green";
        } else {
            $_SESSION['flash_message'] = "Gagal menghapus notifikasi.";
            $_SESSION['flash_type'] = "red";
        }
        header("Location: " . USER_URL . "notifikasi.php");
        exit;
    }
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Ambil daftar notifikasi
    $notifications = $notification->getUserNotifications($userId, $limit, $offset);
    
    // Hitung total notifikasi untuk pagination
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $totalNotifications = $stmt->fetchColumn();
        $totalPages = ceil($totalNotifications / $limit);
    } catch (PDOException $e) {
        error_log("Error counting notifications: " . $e->getMessage());
        $totalNotifications = 0;
        $totalPages = 1;
    }

} catch (Exception $e) {
    error_log("Notification page error: " . $e->getMessage());
    $_SESSION['flash_message'] = "Terjadi kesalahan saat memuat notifikasi. Silakan coba lagi nanti.";
    $_SESSION['flash_type'] = "red";
    $notifications = [];
    $totalNotifications = 0;
    $totalPages = 1;
}

// Include header
include_once 'includes/header.php';
?>

<!-- Notifikasi Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Notifikasi</h2>
                    
                    <?php if (count($notifications) > 0): ?>
                    <a href="<?= USER_URL ?>notifikasi.php?action=mark_all_read" class="text-blue-500 hover:text-blue-700 text-sm">
                        <i class="fas fa-check-double mr-1"></i> Tandai semua dibaca
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (count($notifications) === 0): ?>
            <div class="p-12 text-center">
                <div class="inline-flex rounded-full bg-gray-100 p-6 mb-4">
                    <i class="fas fa-bell-slash text-gray-500 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-800 mb-2">Tidak ada notifikasi</h3>
                <p class="text-gray-600">Anda belum memiliki notifikasi apapun.</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($notifications as $item): ?>
                <div class="p-6 <?= $item['status'] === 'belum_dibaca' ? 'bg-blue-50' : 'bg-white' ?> hover:bg-gray-50 transition-colors">
                    <div class="flex items-start">
                        <!-- Icon berdasarkan tipe notifikasi -->
                        <?php 
                        $iconBg = 'bg-gray-500';
                        $icon = 'fa-bell';
                        
                        switch ($item['tipe']) {
                            case 'pembayaran':
                                $iconBg = 'bg-blue-500';
                                $icon = 'fa-credit-card';
                                break;
                            case 'konfirmasi':
                                $iconBg = 'bg-green-500';
                                $icon = 'fa-check-circle';
                                break;
                            case 'pengembalian':
                                $iconBg = 'bg-yellow-500';
                                $icon = 'fa-car';
                                break;
                            case 'ulasan':
                                $iconBg = 'bg-purple-500';
                                $icon = 'fa-star';
                                break;
                        }
                        ?>
                        <div class="rounded-full p-3 <?= $iconBg ?> text-white mr-4">
                            <i class="fas <?= $icon ?>"></i>
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 flex items-center">
                                        <?= htmlspecialchars($item['judul']) ?>
                                        <?php if ($item['status'] === 'belum_dibaca'): ?>
                                        <span class="inline-block w-2 h-2 bg-blue-500 rounded-full ml-2"></span>
                                        <?php endif; ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($item['pesan']) ?></p>
                                </div>
                                
                                <div class="flex items-center ml-4">
                                    <span class="text-xs text-gray-500 whitespace-nowrap">
                                        <?= date('d M Y H:i', strtotime($item['created_at'])) ?>
                                    </span>
                                    
                                    <div class="dropdown relative ml-3">
                                        <button class="text-gray-400 hover:text-gray-600 p-1">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-100 hidden">
                                            <?php if ($item['status'] === 'belum_dibaca'): ?>
                                            <a href="<?= USER_URL ?>notifikasi.php?id=<?= $item['id'] ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                                                <i class="fas fa-check mr-2"></i> Tandai dibaca
                                            </a>
                                            <?php endif; ?>
                                            <a href="<?= USER_URL ?>notifikasi.php?delete=<?= $item['id'] ?>" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-500 hover:text-white" onclick="return confirm('Yakin ingin menghapus notifikasi ini?')">
                                                <i class="fas fa-trash-alt mr-2"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($item['referensi_id'] && $item['referensi_tabel'] === 'pemesanan'): ?>
                            <div class="mt-3">
                                <a href="<?= USER_URL ?>pemesanan_detail.php?id=<?= $item['referensi_id'] ?>" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-external-link-alt mr-1"></i> Lihat Pesanan
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Menampilkan <?= count($notifications) ?> dari <?= $totalNotifications ?> notifikasi
                    </div>
                    
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                        <a href="<?= USER_URL ?>notifikasi.php?page=<?= $page - 1 ?>" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-100">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="<?= USER_URL ?>notifikasi.php?page=<?= $i ?>" class="px-3 py-1 rounded border <?= $i === $page ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-300 text-gray-600 hover:bg-gray-100' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="<?= USER_URL ?>notifikasi.php?page=<?= $page + 1 ?>" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-100">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle dropdown menu
    const dropdownButtons = document.querySelectorAll('.dropdown button');
    dropdownButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            
            // Close all other menus
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            
            // Toggle current menu
            menu.classList.toggle('hidden');
        });
    });
    
    // Close dropdown on outside click
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?> 