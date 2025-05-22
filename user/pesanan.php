<?php
require_once 'includes/header.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Anda harus login terlebih dahulu";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil filter
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Ambil daftar pemesanan
try {
    $sql = "SELECT p.*, m.merk, m.model, m.nomor_plat, m.foto_mobil 
            FROM pemesanan p
            JOIN mobil m ON p.mobil_id = m.id
            WHERE p.user_id = ?";
    $params = [$user_id];
    
    // Terapkan filter status jika ada
    if (!empty($status)) {
        $sql .= " AND p.status_pemesanan = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $pemesananList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

// Fungsi untuk mendapatkan label status
function getStatusLabel($status) {    
    switch ($status) {        
        case 'menunggu':            
            return '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-clock mr-1 text-yellow-600"></i>Menunggu Pembayaran</span>';
        case 'dibayar':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-money-bill-wave mr-1 text-green-600"></i>Dibayar</span>';        
        case 'dikonfirmasi':            
            return '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-check-circle mr-1 text-blue-600"></i>Dikonfirmasi</span>';        
        case 'berjalan':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-car mr-1 text-green-600"></i>Berjalan</span>';        
        case 'selesai':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-check-double mr-1 text-green-600"></i>Selesai</span>';        
        case 'dibatalkan':            
            return '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-times-circle mr-1 text-red-600"></i>Dibatalkan</span>';
        default:            
            return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-question-circle mr-1 text-gray-600"></i>' . ucfirst(str_replace('_', ' ', $status ?? '')) . '</span>';
    }
}
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-3">
    <div class="container mx-auto px-6">
        <div class="flex text-sm">
            <a href="<?= USER_URL ?>" class="text-blue-600 hover:text-blue-800">Beranda</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-600">Pesanan Saya</span>
        </div>
    </div>
</div>

<!-- Pemesanan List Section -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Pesanan Saya</h1>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200 mb-6">
            <form action="" method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter Status</label>
                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                                <option value="">Semua Status</option>                        <option value="menunggu" <?= $status === 'menunggu' ? 'selected' : '' ?>>Menunggu Pembayaran</option>                        <option value="dibayar" <?= $status === 'dibayar' ? 'selected' : '' ?>>Dibayar</option>                        <option value="dikonfirmasi" <?= $status === 'dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>                        <option value="berjalan" <?= $status === 'berjalan' ? 'selected' : '' ?>>Berjalan</option>                        <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>                        <option value="dibatalkan" <?= $status === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                    
                    <?php if (!empty($status)): ?>
                    <a href="<?= USER_URL ?>pesanan.php" class="ml-2 text-blue-600 hover:text-blue-800 flex items-center">
                        <i class="fas fa-times-circle mr-1"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
                <p>Terjadi kesalahan: <?= $error_message ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Pemesanan List -->
        <?php if (empty($pemesananList)): ?>
            <div class="text-center py-16 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200">
                <div class="flex flex-col items-center justify-center">
                    <div class="w-28 h-28 flex items-center justify-center rounded-full bg-blue-100 text-blue-500 mb-6">
                        <i class="fas fa-calendar-alt text-5xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Belum Ada Pesanan</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">Anda belum memiliki riwayat pemesanan mobil. Mulai pesan mobil sekarang untuk perjalanan Anda!</p>
                    <a href="<?= USER_URL ?>mobil.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-all inline-block shadow-md hover:shadow-lg flex items-center justify-center">
                        <i class="fas fa-car mr-2"></i> Lihat Katalog Mobil
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($pemesananList as $pemesanan): ?>
                    <?php 
                        $tanggal_mulai = new DateTime($pemesanan['tanggal_mulai']);
                        $tanggal_selesai = new DateTime($pemesanan['tanggal_selesai']);
                        $durasi = $tanggal_mulai->diff($tanggal_selesai)->days;
                    ?>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200 hover:shadow-md transition-all">
                        <div class="grid grid-cols-1 md:grid-cols-12">
                            <!-- Gambar Mobil -->
                            <div class="md:col-span-3 h-48 md:h-48 bg-gray-100 relative">
                                <?php if (!empty($pemesanan['foto_mobil'])): ?>
                                    <img src="<?= ASSETS_URL ?>uploads/mobil/<?= $pemesanan['foto_mobil'] ?>" alt="<?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <img src="<?= ASSETS_URL ?>images/car-login.jpg" alt="<?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?>" class="w-full h-full object-cover">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Detail Pemesanan -->
                            <div class="md:col-span-9 p-6">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-3">
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-800 mb-1 truncate max-w-xs"><?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?></h2>
                                        <p class="text-gray-500 text-xs mb-2"><i class="fas fa-id-card mr-1"></i> <?= $pemesanan['nomor_plat'] ?></p>
                                    </div>
                                    <?= getStatusLabel($pemesanan['status_pemesanan']) ?>
                                </div>
                                
                                <p class="text-gray-600 text-sm mb-3">Kode: <span class="font-medium"><?= $pemesanan['kode_pemesanan'] ?></span></p>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                    <div>
                                        <p class="text-gray-600 text-sm mb-1">Mulai: <span class="font-medium"><?= date('d F Y', strtotime($pemesanan['tanggal_mulai'])) ?></span></p>
                                        <p class="text-gray-600 text-sm mb-1">Selesai: <span class="font-medium"><?= date('d F Y', strtotime($pemesanan['tanggal_selesai'])) ?></span></p>
                                        <p class="text-gray-600 text-sm">Durasi: <span class="font-medium"><?= $durasi ?> hari</span></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600 text-sm mb-1">Total: <span class="font-semibold text-blue-600">Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?></span></p>
                                        <p class="text-gray-600 text-sm">Tanggal Pesan: <span class="font-medium"><?= date('d F Y', strtotime($pemesanan['created_at'])) ?></span></p>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-2 pt-3 border-t border-gray-100">
                                    <?php if ($pemesanan['status_pemesanan'] === 'menunggu'): ?>
                                        <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $pemesanan['kode_pemesanan'] ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all text-center flex items-center justify-center">
                                            <i class="fas fa-credit-card mr-2"></i> Bayar Sekarang
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $pemesanan['kode_pemesanan'] ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all text-center flex items-center justify-center">
                                            <i class="fas fa-eye mr-2"></i> Lihat Detail
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-6">
        <div class="bg-gradient-to-r from-blue-700 to-blue-500 rounded-xl p-8 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-48 h-48 bg-white opacity-10 rounded-full -mt-16 -mr-16"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white opacity-10 rounded-full -mb-16 -ml-16"></div>
            <div class="relative z-10 text-center text-white">
                <h2 class="text-2xl font-bold mb-3">Butuh mobil untuk perjalanan Anda?</h2>
                <p class="text-lg opacity-90 mb-6 max-w-3xl mx-auto">Kami memiliki berbagai pilihan mobil sesuai kebutuhan Anda. Pesan sekarang dan nikmati perjalanan yang nyaman!</p>
                <a href="<?= USER_URL ?>mobil.php" class="bg-white text-blue-600 font-semibold py-3 px-8 rounded-lg hover:bg-blue-50 transition duration-300 inline-flex items-center justify-center shadow-md">
                    <i class="fas fa-car-side mr-2"></i> Lihat Katalog Mobil
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?> 