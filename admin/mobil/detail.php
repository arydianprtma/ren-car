<?php
/**
 * Detail Mobil - Admin Panel
 */
require_once '../includes/auth_check.php';

// Inisialisasi variabel
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$errors = [];

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
    $stmt = $conn->prepare("
        SELECT m.*, k.nama_kategori 
        FROM mobil m
        LEFT JOIN kategori_mobil k ON m.kategori_id = k.id
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $mobil = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mobil) {
        $_SESSION['flash_message'] = 'Mobil tidak ditemukan';
        $_SESSION['flash_type'] = 'red';
        header("Location: " . ADMIN_URL . "mobil/index.php");
        exit;
    }
    
    // Ambil data pemesanan terkait mobil ini
    $stmt = $conn->prepare("
        SELECT p.*, u.nama as nama_pelanggan
        FROM pemesanan p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.mobil_id = ?
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$id]);
    $pemesanan_terkait = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil jumlah total pemesanan untuk mobil ini
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pemesanan WHERE mobil_id = ?");
    $stmt->execute([$id]);
    $total_pemesanan = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'red';
    header("Location: " . ADMIN_URL . "mobil/index.php");
    exit;
}

// Proses jika ada request hapus
if (isset($_POST['action']) && $_POST['action'] === 'hapus') {
    try {
        // Mulai transaksi
        $conn->beginTransaction();
        
        // Cek apakah mobil sedang digunakan dalam pemesanan
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pemesanan WHERE mobil_id = ? AND status IN ('menunggu_konfirmasi', 'dikonfirmasi', 'berjalan')");
        $stmt->execute([$id]);
        $pemesanan_aktif = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($pemesanan_aktif > 0) {
            $_SESSION['flash_message'] = 'Mobil tidak dapat dihapus karena sedang digunakan dalam pemesanan aktif';
            $_SESSION['flash_type'] = 'red';
            header("Location: " . ADMIN_URL . "mobil/detail.php?id=" . $id);
            exit;
        }
        
        // Hapus file foto jika ada
        if (!empty($mobil['foto_mobil'])) {
            $file_path = '../../assets/uploads/mobil/' . $mobil['foto_mobil'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Hapus data mobil
        $stmt = $conn->prepare("DELETE FROM mobil WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaksi
        $conn->commit();
        
        $_SESSION['flash_message'] = 'Mobil berhasil dihapus!';
        $_SESSION['flash_type'] = 'green';
        
        // Redirect ke halaman daftar mobil
        header("Location: " . ADMIN_URL . "mobil/index.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollback();
        
        $_SESSION['flash_message'] = 'Gagal menghapus mobil: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'red';
        header("Location: " . ADMIN_URL . "mobil/detail.php?id=" . $id);
        exit;
    }
}

// Format status untuk tampilan
$status_labels = [
    'tersedia' => '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Tersedia</span>',
    'disewa' => '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Sedang Disewa</span>',
    'pemeliharaan' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Pemeliharaan</span>'
];

// Format status pemesanan untuk tampilan
$status_pemesanan_labels = [
    'menunggu_konfirmasi' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Menunggu Konfirmasi</span>',
    'dikonfirmasi' => '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Dikonfirmasi</span>',
    'berjalan' => '<span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">Sedang Berjalan</span>',
    'selesai' => '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Selesai</span>',
    'dibatalkan' => '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Dibatalkan</span>'
];

// Sisipkan header
require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-car mr-3 text-primary-600"></i> Detail Mobil
        </h1>
        <p class="text-sm text-gray-600">Informasi lengkap tentang mobil</p>
    </div>
    <div class="flex space-x-3">
        <a href="<?= ADMIN_URL ?>mobil/edit.php?id=<?= $id ?>" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fas fa-edit mr-2"></i> Edit
        </a>
        <a href="<?= ADMIN_URL ?>mobil/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Detail Mobil -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Foto Mobil -->
            <div class="lg:col-span-1">
                <div class="bg-gray-100 rounded-lg overflow-hidden h-60 md:h-80 flex items-center justify-center">
                    <?php if (!empty($mobil['foto_mobil'])): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/mobil/<?= $mobil['foto_mobil'] ?>" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <img src="<?= BASE_URL ?>assets/images/car-login.jpg" alt="Default Car" class="w-full h-full object-cover">
                    <?php endif; ?>
                </div>
                
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Tindakan</h3>
                    <div class="flex flex-col space-y-3">
                        <a href="<?= ADMIN_URL ?>mobil/edit.php?id=<?= $id ?>" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center justify-center">
                            <i class="fas fa-edit mr-2"></i> Edit Data Mobil
                        </a>
                        
                        <button type="button" id="btn-hapus" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center justify-center">
                            <i class="fas fa-trash-alt mr-2"></i> Hapus Mobil
                        </button>
                        
                        <?php if ($mobil['status'] === 'tersedia'): ?>
                        <a href="<?= ADMIN_URL ?>pemesanan/tambah.php?mobil_id=<?= $id ?>" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center justify-center">
                            <i class="fas fa-calendar-plus mr-2"></i> Buat Pemesanan
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Form Konfirmasi Hapus (hidden by default) -->
                    <div id="konfirmasi-hapus" class="hidden mt-4 p-3 border border-red-300 bg-red-50 rounded-lg">
                        <p class="text-red-700 mb-3">Anda yakin ingin menghapus mobil ini? Tindakan ini tidak dapat dibatalkan.</p>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="hapus">
                            <div class="flex space-x-3">
                                <button type="button" id="btn-batal" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg transition duration-200">Batal</button>
                                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition duration-200">Ya, Hapus</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Informasi Mobil -->
            <div class="lg:col-span-2">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($mobil['merk']) ?> <?= htmlspecialchars($mobil['model']) ?></h2>
                    <div>
                        <?= $status_labels[$mobil['status']] ?? '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">Unknown</span>' ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500">Nomor Plat</h3>
                        <p class="text-lg font-semibold"><?= htmlspecialchars($mobil['nomor_plat']) ?></p>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500">Kategori</h3>
                        <p class="text-lg font-semibold"><?= htmlspecialchars($mobil['nama_kategori'] ?? 'Tidak ada kategori') ?></p>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500">Harga Sewa</h3>
                        <p class="text-lg font-semibold text-primary-600">Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?> <span class="text-sm text-gray-500 font-normal">/ Hari</span></p>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500">Total Pemesanan</h3>
                        <p class="text-lg font-semibold"><?= $total_pemesanan ?> pemesanan</p>
                    </div>
                </div>
                
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Spesifikasi</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt w-6 text-primary-500"></i>
                            <span class="text-sm font-medium text-gray-500 w-32">Tahun Produksi</span>
                            <span class="text-sm font-medium"><?= $mobil['tahun_produksi'] ?></span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-palette w-6 text-primary-500"></i>
                            <span class="text-sm font-medium text-gray-500 w-32">Warna</span>
                            <span class="text-sm font-medium"><?= htmlspecialchars($mobil['warna']) ?></span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-user-friends w-6 text-primary-500"></i>
                            <span class="text-sm font-medium text-gray-500 w-32">Kapasitas</span>
                            <span class="text-sm font-medium"><?= $mobil['kapasitas'] ?> Orang</span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-cog w-6 text-primary-500"></i>
                            <span class="text-sm font-medium text-gray-500 w-32">Transmisi</span>
                            <span class="text-sm font-medium"><?= ucfirst($mobil['transmisi']) ?></span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-gas-pump w-6 text-primary-500"></i>
                            <span class="text-sm font-medium text-gray-500 w-32">Bahan Bakar</span>
                            <span class="text-sm font-medium"><?= ucfirst($mobil['bahan_bakar']) ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($mobil['deskripsi'])): ?>
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Deskripsi</h3>
                        <p class="text-gray-600 whitespace-pre-line"><?= nl2br(htmlspecialchars($mobil['deskripsi'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Informasi Tambahan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                            <div class="flex items-center">
                                <i class="fas fa-clock w-6 text-primary-500"></i>
                                <span class="text-sm font-medium text-gray-500 w-32">Dibuat pada</span>
                                <span class="text-sm font-medium"><?= date('d M Y H:i', strtotime($mobil['created_at'])) ?></span>
                            </div>
                            
                            <?php if (!empty($mobil['updated_at'])): ?>
                            <div class="flex items-center">
                                <i class="fas fa-edit w-6 text-primary-500"></i>
                                <span class="text-sm font-medium text-gray-500 w-32">Diperbarui pada</span>
                                <span class="text-sm font-medium"><?= date('d M Y H:i', strtotime($mobil['updated_at'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pemesanan Terkait -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-semibold text-gray-800">Pemesanan Terkait</h3>
    </div>
    
    <div class="p-6">
        <?php if (empty($pemesanan_terkait)): ?>
        <div class="text-center py-8">
            <div class="mb-3 text-gray-400">
                <i class="fas fa-calendar-times text-4xl"></i>
            </div>
            <h4 class="text-gray-700 font-medium mb-1">Belum ada pemesanan</h4>
            <p class="text-gray-500 text-sm">Mobil ini belum pernah dipesan</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Pemesanan</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Sewa</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($pemesanan_terkait as $pemesanan): ?>
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $pemesanan['id'] ?></td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($pemesanan['nama_pelanggan']) ?></td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?= date('d M Y', strtotime($pemesanan['created_at'])) ?></td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            <?= date('d M Y', strtotime($pemesanan['tanggal_mulai'])) ?> - 
                            <?= date('d M Y', strtotime($pemesanan['tanggal_selesai'])) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <?= $status_pemesanan_labels[$pemesanan['status']] ?? '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">Unknown</span>' ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                            <a href="<?= ADMIN_URL ?>pemesanan/detail.php?id=<?= $pemesanan['id'] ?>" class="text-primary-600 hover:text-primary-700">Detail</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pemesanan > 5): ?>
        <div class="mt-4 text-center">
            <a href="<?= ADMIN_URL ?>pemesanan/index.php?mobil_id=<?= $id ?>" class="text-primary-600 hover:text-primary-700 font-medium flex items-center justify-center">
                Lihat Semua Pemesanan <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnHapus = document.getElementById('btn-hapus');
    const btnBatal = document.getElementById('btn-batal');
    const konfirmasiHapus = document.getElementById('konfirmasi-hapus');
    
    btnHapus.addEventListener('click', function() {
        konfirmasiHapus.classList.remove('hidden');
    });
    
    btnBatal.addEventListener('click', function() {
        konfirmasiHapus.classList.add('hidden');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 