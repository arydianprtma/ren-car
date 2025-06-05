<?php
/**
 * Manajemen Pemesanan - Admin Panel
 */
require_once '../includes/auth_check.php';
require_once '../includes/header.php';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil parameter pencarian & filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Item per halaman
$offset = ($page - 1) * $limit;

// Siapkan query dasar
$sql = "SELECT p.*, m.merk, m.model, m.nomor_plat, u.nama as nama_penyewa
        FROM pemesanan p
        JOIN mobil m ON p.mobil_id = m.id
        JOIN users u ON p.user_id = u.id
        WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM pemesanan p
             JOIN mobil m ON p.mobil_id = m.id
             JOIN users u ON p.user_id = u.id
             WHERE 1=1";
$params = [];

// Tambahkan kondisi pencarian jika ada
if (!empty($search)) {
    $sql .= " AND (p.kode_pemesanan LIKE ? OR m.merk LIKE ? OR m.model LIKE ? OR u.nama LIKE ? OR m.nomor_plat LIKE ?)";
    $countSql .= " AND (p.kode_pemesanan LIKE ? OR m.merk LIKE ? OR m.model LIKE ? OR u.nama LIKE ? OR m.nomor_plat LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

// Tambahkan filter status jika dipilih
if (!empty($status)) {
    $sql .= " AND p.status_pemesanan = ?";
    $countSql .= " AND p.status_pemesanan = ?";
    $params[] = $status;
}

// Tambahkan pengurutan dan batasan
$sql .= " ORDER BY p.created_at DESC LIMIT $offset, $limit";

// Eksekusi query
try {
    // Hitung total data untuk paginasi
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Ambil data pemesanan
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $pemesananList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    $pemesananList = [];
    $totalPages = 0;
    $totalRecords = 0;
}

// Fungsi untuk mendapatkan label status
function getStatusLabel($status_pemesanan) {    
    // Pastikan status tidak null/empty
    if (empty($status_pemesanan)) {
        return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">Menunggu</span>';
    }
    
    switch ($status_pemesanan) {        
        case 'menunggu':            
            return '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-medium">Menunggu Pembayaran</span>';       
        case 'dikonfirmasi':            
            return '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">Dikonfirmasi</span>';        
        case 'berjalan':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">Berjalan</span>';        
        case 'selesai':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">Selesai</span>';        
        case 'dibatalkan':            
            return '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-medium">Dibatalkan</span>';        
        default:            
            return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">' . ucfirst(str_replace('_', ' ', $status_pemesanan)) . '</span>';    
    }
}

// Fungsi untuk mendapatkan label metode pembayaran
function getMetodePembayaranLabel($metode) {
    // Pastikan metode tidak null/empty
    if (empty($metode)) {
        return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">Belum dipilih</span>';
    }
    
    switch ($metode) {
        case 'transfer_bank':
            return '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">Transfer Bank</span>';
        case 'tunai':
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">Bayar di Tempat</span>';
        case 'e-wallet':
            return '<span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-xs font-medium">E-Wallet</span>';
        default:
            return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium">' . ucfirst(str_replace('_', ' ', $metode)) . '</span>';
    }
}

// Fungsi untuk menghasilkan URL dengan mempertahankan parameter yang ada
function getPageUrl($page, $search, $status) {
    $params = ['page' => $page];
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    if (!empty($status)) {
        $params['status'] = $status;
    }
    
    return '?' . http_build_query($params);
}
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-6 space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-clipboard-list mr-2 sm:mr-3 text-primary-600"></i> Manajemen Pemesanan
        </h1>
        <p class="text-sm text-gray-600">Kelola semua pemesanan dalam sistem</p>
    </div>
    <a href="<?= ADMIN_URL ?>fix_database.php" class="bg-orange-500 hover:bg-orange-600 text-white py-2 px-3 sm:px-4 rounded-lg shadow-sm transition duration-200 flex items-center text-sm">
        <i class="fas fa-tools mr-2"></i> Perbaiki Database
    </a>
</div>

<!-- Filters & Search -->
<div class="bg-white rounded-lg shadow-sm mb-6 p-4">
    <form action="" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Pemesanan</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors text-sm" placeholder="Kode, penyewa, mobil...">
        </div>
        
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter Status</label>
            <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors text-sm">
                <option value="">Semua Status</option>
                <option value="menunggu" <?= $status === 'menunggu' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                <option value="dibayar" <?= $status === 'dibayar' ? 'selected' : '' ?>>Dibayar</option>
                <option value="dikonfirmasi" <?= $status === 'dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                <option value="berjalan" <?= $status === 'berjalan' ? 'selected' : '' ?>>Berjalan</option>
                <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                <option value="dibatalkan" <?= $status === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
        </div>
        
        <div class="flex flex-col sm:flex-row items-end space-y-2 sm:space-y-0 sm:space-x-2">
            <button type="submit" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition duration-200 text-sm">
                <i class="fas fa-search mr-2"></i> Cari
            </button>
            
            <?php if (!empty($search) || !empty($status)): ?>
                <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200 text-center text-sm">
                    <i class="fas fa-times mr-2"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Stats Cards -->
<?php
// Query untuk menghitung jumlah pemesanan per status
$statsSql = "SELECT status_pemesanan, COUNT(*) as total FROM pemesanan GROUP BY status_pemesanan";
$statsStmt = $conn->query($statsSql);
$stats = [];

while ($row = $statsStmt->fetch(PDO::FETCH_ASSOC)) {
    $stats[$row['status_pemesanan']] = $row['total'];
}

// Tentukan total untuk setiap status (jika tidak ada, set ke 0)
$menungguPembayaran = $stats['menunggu'] ?? 0;
$dibayar = $stats['dibayar'] ?? 0;
$menungguKonfirmasi = $stats['dikonfirmasi'] ?? 0;
$berjalan = $stats['berjalan'] ?? 0; 
$diproses = $berjalan; 
$selesai = $stats['selesai'] ?? 0;
$dibatalkan = $stats['dibatalkan'] ?? 0;
$totalSemua = array_sum($stats);
?>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-2 sm:gap-4 mb-6">
    <!-- Total Pemesanan -->
    <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md transition-all stats-card">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 bg-blue-100 rounded-lg">
                <i class="fas fa-clipboard-list text-blue-600 text-sm sm:text-base"></i>
            </div>
            <div class="ml-2 sm:ml-3">
                <p class="text-xs sm:text-sm text-gray-500">Total</p>
                <p class="text-lg sm:text-xl font-bold text-gray-800"><?= $totalSemua ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <!-- Menunggu Pembayaran -->
    <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md transition-all stats-card">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 bg-yellow-100 rounded-lg">
                <i class="fas fa-clock text-yellow-600 text-sm sm:text-base"></i>
            </div>
            <div class="ml-2 sm:ml-3">
                <p class="text-xs sm:text-sm text-gray-500">Menunggu Bayar</p>
                <p class="text-lg sm:text-xl font-bold text-gray-800"><?= $menungguPembayaran ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <!-- Dibayar -->
    <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md transition-all stats-card">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 bg-green-100 rounded-lg">
                <i class="fas fa-money-bill-wave text-green-600 text-sm sm:text-base"></i>
            </div>
            <div class="ml-2 sm:ml-3">
                <p class="text-xs sm:text-sm text-gray-500">Dibayar</p>
                <p class="text-lg sm:text-xl font-bold text-gray-800"><?= $dibayar ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <!-- Menunggu Konfirmasi -->
    <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md transition-all stats-card">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 bg-blue-100 rounded-lg">
                <i class="fas fa-clipboard-check text-blue-600 text-sm sm:text-base"></i>
            </div>
            <div class="ml-2 sm:ml-3">
                <p class="text-xs sm:text-sm text-gray-500">Konfirmasi</p>
                <p class="text-lg sm:text-xl font-bold text-gray-800"><?= $menungguKonfirmasi ?? 0 ?></p>
            </div>
        </div>
    </div>
    
        <!-- Diproses -->    <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md transition-all stats-card">        <div class="flex items-center">            <div class="p-2 sm:p-3 bg-indigo-100 rounded-lg">                <i class="fas fa-cogs text-indigo-600 text-sm sm:text-base"></i>            </div>            <div class="ml-2 sm:ml-3">                <p class="text-xs sm:text-sm text-gray-500">Diproses</p>                <p class="text-lg sm:text-xl font-bold text-gray-800"><?= $diproses ?? 0 ?></p>            </div>        </div>    </div>        <!-- Berjalan -->    <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md transition-all stats-card">        <div class="flex items-center">            <div class="p-2 sm:p-3 bg-green-100 rounded-lg">                <i class="fas fa-car text-green-600 text-sm sm:text-base"></i>            </div>            <div class="ml-2 sm:ml-3">                <p class="text-xs sm:text-sm text-gray-500">Berjalan</p>                <p class="text-lg sm:text-xl font-bold text-gray-800"><?= $berjalan ?? 0 ?></p>            </div>        </div>    </div>
    
    <!-- Selesai -->
    <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md transition-all stats-card">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 bg-green-100 rounded-lg">
                <i class="fas fa-check-circle text-green-600 text-sm sm:text-base"></i>
            </div>
            <div class="ml-2 sm:ml-3">
                <p class="text-xs sm:text-sm text-gray-500">Selesai</p>
                <p class="text-lg sm:text-xl font-bold text-gray-800"><?= $selesai ?? 0 ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Pemesanan Table -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <?php if (empty($pemesananList)): ?>
        <div class="p-4 sm:p-6 text-center">
            <div class="mb-4 flex justify-center">
                <div class="w-12 sm:w-16 h-12 sm:h-16 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-search text-gray-400 text-xl sm:text-2xl"></i>
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-1">Tidak ada pemesanan ditemukan</h3>
            <p class="text-sm sm:text-base text-gray-600 mb-4">
                <?php if (!empty($search) || !empty($status)): ?>
                    Tidak ada hasil yang cocok dengan kriteria pencarian Anda.
                <?php else: ?>
                    Belum ada pemesanan dalam sistem.
                <?php endif; ?>
            </p>
            <?php if (!empty($search) || !empty($status)): ?>
                <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="text-primary-600 hover:text-primary-700 font-medium text-sm">Reset Pencarian</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kode Pemesanan
                        </th>
                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mobil
                        </th>
                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                            Penyewa
                        </th>
                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                            Tanggal Sewa
                        </th>
                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                            Total
                        </th>
                        <th scope="col" class="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($pemesananList as $pemesanan): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900 text-sm"><?= $pemesanan['kode_pemesanan'] ?></div>
                                <div class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($pemesanan['created_at'])) ?></div>
                            </td>
                            <td class="px-3 sm:px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm"><?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?></div>
                                <div class="text-xs text-gray-500"><?= $pemesanan['nomor_plat'] ?></div>
                                <div class="text-xs text-gray-500 lg:hidden mt-1">
                                    <i class="fas fa-user mr-1"></i><?= $pemesanan['nama_penyewa'] ?>
                                </div>
                                <div class="text-xs text-gray-500 md:hidden mt-1">
                                    <i class="fas fa-calendar mr-1"></i><?= date('d/m/Y', strtotime($pemesanan['tanggal_mulai'])) ?> - <?= date('d/m/Y', strtotime($pemesanan['tanggal_selesai'])) ?>
                                </div>
                                <div class="text-xs text-gray-500 sm:hidden mt-1">
                                    <i class="fas fa-money-bill-wave mr-1"></i>Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div class="text-sm text-gray-900"><?= $pemesanan['nama_penyewa'] ?></div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-gray-900"><?= date('d/m/Y', strtotime($pemesanan['tanggal_mulai'])) ?></div>
                                <div class="text-xs text-gray-500">s/d <?= date('d/m/Y', strtotime($pemesanan['tanggal_selesai'])) ?></div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-1">
                                    <div><?= getStatusLabel($pemesanan['status_pemesanan']) ?></div>
                                    <?php if (!empty($pemesanan['metode_pembayaran'])): ?>
                                    <div>
                                        <?= getMetodePembayaranLabel($pemesanan['metode_pembayaran']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium hidden sm:table-cell">
                                Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?>
                            </td>
                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="detail.php?id=<?= $pemesanan['id'] ?>" class="text-primary-600 hover:text-primary-800 text-xs sm:text-sm">
                                    <i class="fas fa-eye"></i> <span class="hidden sm:inline">Detail</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-6 space-y-4 sm:space-y-0">
    <div class="text-sm text-gray-600 text-center sm:text-left">
        Menampilkan <?= min($offset + 1, $totalRecords) ?> - <?= min($offset + $limit, $totalRecords) ?> dari <?= $totalRecords ?> pemesanan
    </div>
    <div class="flex justify-center sm:justify-end space-x-1">
        <?php if ($page > 1): ?>
            <a href="<?= getPageUrl(1, $search, $status) ?>" class="px-2 sm:px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm">
                <i class="fas fa-angle-double-left"></i>
            </a>
            <a href="<?= getPageUrl($page - 1, $search, $status) ?>" class="px-2 sm:px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm">
                <i class="fas fa-angle-left"></i>
            </a>
        <?php endif; ?>
        
        <?php
        // Tampilkan maksimal 5 nomor halaman di desktop, 3 di mobile
        $maxPages = 3; // untuk mobile
        $startPage = max(1, min($page - 1, $totalPages - ($maxPages - 1)));
        $endPage = min($totalPages, max($page + 1, $maxPages));
        
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a href="<?= getPageUrl($i, $search, $status) ?>" class="px-2 sm:px-3 py-1 rounded border text-sm <?= $i === $page ? 'bg-primary-100 border-primary-300 text-primary-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="<?= getPageUrl($page + 1, $search, $status) ?>" class="px-2 sm:px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm">
                <i class="fas fa-angle-right"></i>
            </a>
            <a href="<?= getPageUrl($totalPages, $search, $status) ?>" class="px-2 sm:px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm">
                <i class="fas fa-angle-double-right"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?> 