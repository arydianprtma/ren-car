<?php
/**
 * Detail Mobil - Admin Panel
 */
require_once '../includes/auth_check.php';

// Inisialisasi variabel
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect jika tidak ada ID
if ($id <= 0) {
    $_SESSION['flash_message'] = 'ID mobil tidak valid';
    $_SESSION['flash_type'] = 'red';
    header("Location: " . ADMIN_URL . "mobil/index.php");
    exit;
}

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil data mobil dari database
try {
    $sql = "SELECT m.*, k.nama_kategori 
            FROM mobil m 
            LEFT JOIN kategori_mobil k ON m.kategori_id = k.id 
            WHERE m.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $mobil = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mobil) {
        $_SESSION['flash_message'] = 'Mobil tidak ditemukan';
        $_SESSION['flash_type'] = 'red';
        header("Location: " . ADMIN_URL . "mobil/index.php");
        exit;
    }
    
    // Cek jumlah pemesanan untuk mobil ini
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pemesanan WHERE mobil_id = ?");
    $stmt->execute([$id]);
    $total_pemesanan = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'red';
    header("Location: " . ADMIN_URL . "mobil/index.php");
    exit;
}

// Ambil data pemesanan terakhir untuk mobil ini (5 terakhir)
try {
    $sql = "SELECT p.*, u.nama as nama_pelanggan
            FROM pemesanan p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.mobil_id = ?
            ORDER BY p.tanggal_pemesanan DESC
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $pemesanan_terakhir = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pemesanan_terakhir = [];
}

// Setelah semua pemrosesan dan redirect selesai, baru include header.php
require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-car mr-3 text-primary-600"></i> Detail Mobil
        </h1>
        <p class="text-sm text-gray-600">Informasi lengkap tentang mobil</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= ADMIN_URL ?>mobil/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <a href="<?= ADMIN_URL ?>mobil/edit.php?id=<?= $mobil['id'] ?>" class="bg-amber-500 hover:bg-amber-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fas fa-edit mr-2"></i> Edit
        </a>
    </div>
</div>

<!-- Skeleton Loader -->
<div id="skeleton-loader" class="bg-white rounded-lg shadow-sm p-6 animate-pulse">
    <div class="flex flex-col md:flex-row gap-6">
        <div class="w-full md:w-1/3 lg:w-1/4">
            <div class="h-64 bg-gray-200 rounded-lg mb-4"></div>
            <div class="h-8 bg-gray-200 w-full mb-2 rounded"></div>
            <div class="h-6 bg-gray-200 w-3/4 rounded"></div>
        </div>
        <div class="w-full md:w-2/3 lg:w-3/4">
            <div class="h-10 bg-gray-200 w-3/4 mb-6 rounded"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="h-10 bg-gray-200 rounded"></div>
                <?php endfor; ?>
            </div>
            <div class="h-40 bg-gray-200 rounded mb-6"></div>
            <div class="h-8 bg-gray-200 w-1/2 mb-4 rounded"></div>
            <div class="h-32 bg-gray-200 rounded"></div>
        </div>
    </div>
</div>

<!-- Detail Mobil -->
<div id="content" class="hidden">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="p-6">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Foto Mobil -->
                <div class="w-full md:w-1/3 lg:w-1/4">
                    <div class="bg-gray-100 rounded-lg overflow-hidden mb-4 shadow-sm">
                        <?php if (!empty($mobil['foto_mobil']) && file_exists('../../assets/uploads/mobil/' . $mobil['foto_mobil'])): ?>
                            <img src="<?= BASE_URL ?>assets/uploads/mobil/<?= $mobil['foto_mobil'] ?>" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-auto object-cover">
                        <?php else: ?>
                            <div class="w-full h-64 flex items-center justify-center bg-gray-200 text-gray-400">
                                <i class="fas fa-car text-5xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-center mb-4">
                        <?php
                        $statusClasses = [
                            'tersedia' => 'bg-green-100 text-green-800 border-green-200',
                            'disewa' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'pemeliharaan' => 'bg-amber-100 text-amber-800 border-amber-200'
                        ];
                        $statusClass = $statusClasses[$mobil['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                        ?>
                        <span class="px-4 py-2 text-sm font-semibold rounded-full border <?= $statusClass ?>">
                            <?= ucfirst($mobil['status']) ?>
                        </span>
                    </div>
                    
                    <div class="text-center text-2xl font-bold text-primary-600 mb-2">
                        Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?> <span class="text-sm text-gray-500 font-normal">/ hari</span>
                    </div>
                    
                    <div class="text-center text-sm text-gray-500">
                        Terakhir diupdate: <?= date('d M Y H:i', strtotime($mobil['updated_at'])) ?>
                    </div>
                </div>
                
                <!-- Informasi Mobil -->
                <div class="w-full md:w-2/3 lg:w-3/4">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <?= $mobil['merk'] ?> <?= $mobil['model'] ?> 
                        <span class="text-lg font-normal text-gray-500"><?= $mobil['tahun_produksi'] ?></span>
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">INFORMASI DASAR</h3>
                            <ul class="space-y-3">
                                <li class="flex items-center text-gray-700">
                                    <div class="w-32 flex items-center">
                                        <i class="fas fa-id-card text-primary-600 mr-2"></i>
                                        <span>Nomor Plat</span>
                                    </div>
                                    <span class="font-medium"><?= $mobil['nomor_plat'] ?></span>
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <div class="w-32 flex items-center">
                                        <i class="fas fa-palette text-primary-600 mr-2"></i>
                                        <span>Warna</span>
                                    </div>
                                    <span class="font-medium"><?= $mobil['warna'] ?></span>
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <div class="w-32 flex items-center">
                                        <i class="fas fa-tag text-primary-600 mr-2"></i>
                                        <span>Kategori</span>
                                    </div>
                                    <span class="font-medium"><?= $mobil['nama_kategori'] ?? 'Tidak Ada Kategori' ?></span>
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <div class="w-32 flex items-center">
                                        <i class="fas fa-history text-primary-600 mr-2"></i>
                                        <span>Pemesanan</span>
                                    </div>
                                    <span class="font-medium"><?= $total_pemesanan ?> kali</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">SPESIFIKASI</h3>
                            <ul class="space-y-3">
                                <li class="flex items-center text-gray-700">
                                    <div class="w-32 flex items-center">
                                        <i class="fas fa-user-friends text-primary-600 mr-2"></i>
                                        <span>Kapasitas</span>
                                    </div>
                                    <span class="font-medium"><?= $mobil['kapasitas'] ?> Orang</span>
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <div class="w-32 flex items-center">
                                        <i class="fas fa-gas-pump text-primary-600 mr-2"></i>
                                        <span>Bahan Bakar</span>
                                    </div>
                                    <span class="font-medium"><?= ucfirst($mobil['bahan_bakar']) ?></span>
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <div class="w-32 flex items-center">
                                        <i class="fas fa-cog text-primary-600 mr-2"></i>
                                        <span>Transmisi</span>
                                    </div>
                                    <span class="font-medium"><?= ucfirst($mobil['transmisi']) ?></span>
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <div class="w-32 flex items-center">
                                        <i class="fas fa-calendar-alt text-primary-600 mr-2"></i>
                                        <span>Tahun</span>
                                    </div>
                                    <span class="font-medium"><?= $mobil['tahun_produksi'] ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">DESKRIPSI</h3>
                        <div class="bg-gray-50 p-4 rounded-lg text-gray-700">
                            <?php if (!empty($mobil['deskripsi'])): ?>
                                <?= nl2br(htmlspecialchars($mobil['deskripsi'])) ?>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tidak ada deskripsi</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($pemesanan_terakhir)): ?>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">RIWAYAT PEMESANAN TERAKHIR</h3>
                        <div class="bg-gray-50 rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pelanggan</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durasi</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($pemesanan_terakhir as $pemesanan): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                                <?= date('d/m/Y', strtotime($pemesanan['tanggal_pemesanan'])) ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700">
                                                <?= $pemesanan['nama_pelanggan'] ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                                <?= $pemesanan['durasi_sewa'] ?> hari
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <?php
                                                $statusLabels = [
                                                    'menunggu' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu</span>',
                                                    'dikonfirmasi' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Dikonfirmasi</span>',
                                                    'berjalan' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Berjalan</span>',
                                                    'selesai' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>',
                                                    'dibatalkan' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Dibatalkan</span>'
                                                ];
                                                echo $statusLabels[$pemesanan['status_pemesanan']] ?? $pemesanan['status_pemesanan'];
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan konten dan sembunyikan skeleton loader setelah page load
    setTimeout(function() {
        document.getElementById('skeleton-loader').classList.add('hidden');
        document.getElementById('content').classList.remove('hidden');
    }, 500);
});
</script>

<?php
require_once '../includes/footer.php';
?> 