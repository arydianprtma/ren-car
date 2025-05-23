<?php
/**
 * Halaman Admin Pengembalian Mobil
 */

// Include auth check untuk memastikan hanya admin yang bisa akses
require_once '../includes/auth_check.php';

// Inisialisasi database
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query base untuk menghitung total data
$baseCountQuery = "SELECT COUNT(*) FROM pemesanan p 
                   JOIN users u ON p.user_id = u.id 
                   JOIN mobil m ON p.mobil_id = m.id 
                   WHERE p.status_pemesanan = 'selesai'";

// Query base untuk mengambil data
$baseQuery = "SELECT p.*, 
              u.nama as nama_user, u.email as email_user, 
              m.model as nama_mobil, m.nomor_plat as plat_nomor 
              FROM pemesanan p 
              JOIN users u ON p.user_id = u.id 
              JOIN mobil m ON p.mobil_id = m.id 
              WHERE p.status_pemesanan = 'selesai'";

// Tambahkan filter jika ada
if ($status_filter) {
    $statusCondition = " AND p.status_pemesanan = :status";
    $baseCountQuery .= $statusCondition;
    $baseQuery .= $statusCondition;
}

// Tambahkan search jika ada
if ($search) {
    $searchCondition = " AND (u.nama LIKE :search OR m.model LIKE :search OR p.kode_pemesanan LIKE :search OR m.nomor_plat LIKE :search)";
    $baseCountQuery .= $searchCondition;
    $baseQuery .= $searchCondition;
}

// Query untuk hitung total
$stmt = $conn->prepare($baseCountQuery);

// Bind parameter jika ada filter
if ($status_filter) {
    $stmt->bindParam(':status', $status_filter);
}

// Bind parameter search
if ($search) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}

$stmt->execute();
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Query untuk ambil data dengan pagination
$query = $baseQuery . " ORDER BY p.tanggal_selesai DESC LIMIT :offset, :limit";
$stmt = $conn->prepare($query);

// Bind parameter untuk filter
if ($status_filter) {
    $stmt->bindParam(':status', $status_filter);
}

// Bind parameter search
if ($search) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$pengembalian = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jumlah pengembalian berdasarkan status untuk card ringkasan
$statusCounts = [];
$statusTypes = ['selesai', 'dibayar', 'menunggu'];

foreach ($statusTypes as $type) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pemesanan WHERE status_pemesanan = :status");
    $stmt->bindParam(':status', $type);
    $stmt->execute();
    $statusCounts[$type] = $stmt->fetchColumn();
}

// Total keterlambatan
$stmt = $conn->prepare("SELECT COUNT(*) FROM pemesanan 
                       WHERE status_pemesanan = 'selesai'
                       AND tanggal_selesai > DATE_ADD(tanggal_mulai, INTERVAL 7 DAY)");
$stmt->execute();
$totalTerlambat = $stmt->fetchColumn();

// Proses ketika admin menandai pengembalian sebagai "selesai"
if (isset($_POST['action']) && $_POST['action'] == 'complete_return' && isset($_POST['pemesanan_id'])) {
    $pemesanan_id = (int)$_POST['pemesanan_id'];
    
    try {
        $conn->beginTransaction();
        
        // Perbarui status pemesanan menjadi "selesai"
        $stmt = $conn->prepare("UPDATE pemesanan SET status_pemesanan = 'selesai' WHERE id = :id");
        $stmt->bindParam(':id', $pemesanan_id);
        $stmt->execute();
        
        // Cek jika ada denda
        $stmt = $conn->prepare("SELECT *, DATEDIFF(NOW(), DATE_ADD(tanggal_mulai, INTERVAL 7 DAY)) as hari_terlambat 
                               FROM pemesanan 
                               WHERE id = :id AND NOW() > DATE_ADD(tanggal_mulai, INTERVAL 7 DAY)");
        $stmt->bindParam(':id', $pemesanan_id);
        $stmt->execute();
        $pemesanan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Menghitung denda jika terlambat
        if ($pemesanan['hari_terlambat'] > 0) {
            // Ambil harga mobil per hari
            $stmt = $conn->prepare("SELECT harga_sewa_per_hari FROM mobil WHERE id = :mobil_id");
            $stmt->bindParam(':mobil_id', $pemesanan['mobil_id']);
            $stmt->execute();
            $mobil = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Hitung denda (50% dari harga sewa per hari terlambat)
            $dendaPerHari = $mobil['harga_sewa_per_hari'] * 0.5;
            $totalDenda = $dendaPerHari * $pemesanan['hari_terlambat'];
            
            // Update denda di database
            $stmt = $conn->prepare("UPDATE pemesanan SET denda = :denda WHERE id = :id");
            $stmt->bindParam(':denda', $totalDenda);
            $stmt->bindParam(':id', $pemesanan_id);
            $stmt->execute();
        }
        
        // Update status mobil menjadi tersedia
        $stmt = $conn->prepare("UPDATE mobil SET status = 'tersedia' WHERE id = (SELECT mobil_id FROM pemesanan WHERE id = :id)");
        $stmt->bindParam(':id', $pemesanan_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Kirim notifikasi ke admin tentang pengembalian
        require_once '../../classes/Notification.php';
        $notif = new Notification($conn);
        $notif->createAdminNotification(
            "Pengembalian Mobil", 
            "Mobil telah dikembalikan untuk pemesanan #{$pemesanan_id}.", 
            "pengembalian", 
            $pemesanan_id, 
            "pemesanan"
        );
        
        // Set flash message
        setFlashMessage("Pengembalian berhasil diproses", "green");
        
        // Redirect untuk refresh halaman
        header("Location: " . ADMIN_URL . "pengembalian/index.php");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        setFlashMessage("Error saat memproses pengembalian: " . $e->getMessage(), "red");
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Pengembalian</h1>
        <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fas fa-clipboard-list mr-2"></i> Lihat Pemesanan
        </a>
    </div>
    
    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all stats-card">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Selesai</p>
                    <p class="text-xl font-bold text-gray-800"><?= $statusCounts['selesai'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all stats-card">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-money-bill-wave text-yellow-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Menunggu</p>
                    <p class="text-xl font-bold text-gray-800"><?= $statusCounts['menunggu'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all stats-card">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-credit-card text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Dibayar</p>
                    <p class="text-xl font-bold text-gray-800"><?= $statusCounts['dibayar'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-all stats-card">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Terlambat</p>
                    <p class="text-xl font-bold text-gray-800"><?= $totalTerlambat ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter dan Pencarian -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form action="" method="get" class="flex flex-wrap items-end gap-4">
            <div class="w-full md:w-1/3">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama pelanggan, mobil, atau kode pemesanan" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="w-full md:w-1/4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Status</option>
                    <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="dibayar" <?= $status_filter === 'dibayar' ? 'selected' : '' ?>>Dibayar</option>
                    <option value="menunggu" <?= $status_filter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
                    <i class="fas fa-search mr-2"></i> Filter
                </button>
                <a href="<?= ADMIN_URL ?>pengembalian/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tabel Pengembalian -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mobil</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Kembali</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Denda</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($pengembalian)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center py-6">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                                    <p>Tidak ada data pengembalian yang ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pengembalian as $item): ?>
                            <?php 
                                // Cek keterlambatan
                                $tanggal_selesai = $item['tanggal_selesai'] ?? date('Y-m-d H:i:s');
                                $tanggal_mulai = $item['tanggal_mulai'];
                                // Menambahkan 7 hari ke tanggal mulai
                                $batas_waktu = date('Y-m-d', strtotime($tanggal_mulai . ' + 7 days'));
                                $terlambat = strtotime($tanggal_selesai) > strtotime($batas_waktu);
                                
                                // Status badge class
                                $statusClass = '';
                                switch ($item['status_pemesanan']) {
                                    case 'selesai':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        break;
                                    case 'dibayar':
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'menunggu':
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        break;
                                    default:
                                        $statusClass = 'bg-gray-100 text-gray-800';
                                }
                            ?>
                            <tr class="<?= $terlambat ? 'bg-red-50' : '' ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $item['id'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['nama_mobil']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($item['plat_nomor']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['nama_user']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($item['email_user']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d M Y', strtotime($item['tanggal_mulai'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d M Y', strtotime($item['tanggal_selesai'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if (!empty($item['tanggal_selesai'])): ?>
                                        <span class="<?= $terlambat ? 'text-red-600 font-medium' : 'text-gray-500' ?>">
                                            <?= date('d M Y', strtotime($item['tanggal_selesai'])) ?>
                                            <?php if ($terlambat): ?>
                                                <span class="block text-xs text-red-500 font-medium">Terlambat</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-yellow-600">Belum dikembalikan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?= $item['denda'] > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' ?>">
                                    <?= $item['denda'] > 0 ? 'Rp ' . number_format($item['denda'], 0, ',', '.') : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                        <?= ucfirst($item['status_pemesanan']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($item['status_pemesanan'] != 'selesai'): ?>
                                        <form method="post" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menandai pengembalian ini sebagai selesai?')">
                                            <input type="hidden" name="action" value="complete_return">
                                            <input type="hidden" name="pemesanan_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-check mr-1"></i> Selesai
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= ADMIN_URL ?>pemesanan/detail.php?id=<?= $item['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye mr-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Menampilkan <?= min(($page - 1) * $perPage + 1, $totalRecords) ?> - <?= min($page * $perPage, $totalRecords) ?> dari <?= $totalRecords ?> hasil
                    </div>
                    <div class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-100">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1 text-sm <?= $i === $page ? 'bg-blue-100 text-blue-700 font-semibold border-blue-500' : 'text-gray-500 bg-white hover:bg-gray-100 border-gray-300' ?> border rounded-md">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-100">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 