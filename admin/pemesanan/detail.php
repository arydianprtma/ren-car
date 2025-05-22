<?php
ob_start(); // Tambahkan output buffering di paling awal
/**
 * Detail Pemesanan - Admin Panel
 */
require_once '../includes/auth_check.php';
require_once '../includes/header.php';

// Periksa apakah parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "ID Pemesanan tidak valid";
    $_SESSION['flash_type'] = "red";
    
    // Pastikan semua output buffer kosong sebelum redirect
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header("Location: " . ADMIN_URL . "pemesanan/index.php");
    exit;
}

$id_pemesanan = $_GET['id'];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Proses update status jika ada POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    $catatan_admin = trim($_POST['catatan_admin'] ?? '');
    
    try {
        // Mulai transaksi
        $conn->beginTransaction();
        
        // Update status pemesanan
        $stmt = $conn->prepare("UPDATE pemesanan SET 
                               status_pemesanan = ?, 
                               catatan_admin = ?,
                               updated_at = NOW() 
                               WHERE id = ?");
        $stmt->execute([$new_status, $catatan_admin, $id_pemesanan]);
        
        // Jika status berubah menjadi 'selesai', update status mobil menjadi 'tersedia' 
        // Jika status berubah menjadi 'dibatalkan', update status mobil menjadi 'tersedia'
        if ($new_status === 'selesai' || $new_status === 'dibatalkan') {
            // Ambil id mobil dari pemesanan
            $stmt = $conn->prepare("SELECT mobil_id FROM pemesanan WHERE id = ?");
            $stmt->execute([$id_pemesanan]);
            $mobil_id = $stmt->fetchColumn();
            
            // Update status mobil
            $stmt = $conn->prepare("UPDATE mobil SET status = 'tersedia' WHERE id = ?");
            $stmt->execute([$mobil_id]);
        }
        // Jika status berubah menjadi 'berjalan', update status mobil menjadi 'disewa'
        elseif ($new_status === 'berjalan') {
            // Ambil id mobil dari pemesanan
            $stmt = $conn->prepare("SELECT mobil_id FROM pemesanan WHERE id = ?");
            $stmt->execute([$id_pemesanan]);
            $mobil_id = $stmt->fetchColumn();
            
            // Update status mobil
            $stmt = $conn->prepare("UPDATE mobil SET status = 'disewa' WHERE id = ?");
            $stmt->execute([$mobil_id]);
        }
        
        // Commit transaksi
        $conn->commit();
        
        // Set flash message
        $_SESSION['flash_message'] = "Status pemesanan berhasil diperbarui menjadi " . ucfirst(str_replace('_', ' ', $new_status));
        $_SESSION['flash_type'] = "green";
        
        // Pastikan semua output buffer kosong sebelum redirect
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Refresh halaman
        header("Location: " . ADMIN_URL . "pemesanan/detail.php?id=" . $id_pemesanan);
        exit;
        
    } catch (PDOException $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollback();
        $_SESSION['flash_message'] = "Gagal memperbarui status: " . $e->getMessage();
        $_SESSION['flash_type'] = "red";
    }
}

// Ambil detail pemesanan
try {
    $stmt = $conn->prepare("SELECT p.*, 
                           m.merk, m.model, m.nomor_plat, m.foto_mobil, m.tahun_produksi, 
                           m.kapasitas, m.transmisi, m.bahan_bakar, m.harga_sewa_per_hari,
                           u.id as user_id, u.nama as nama_user, u.email, u.no_telp as telepon, 
                           k.nama_kategori
                           FROM pemesanan p
                           JOIN mobil m ON p.mobil_id = m.id
                           JOIN users u ON p.user_id = u.id
                           LEFT JOIN kategori_mobil k ON m.kategori_id = k.id
                           WHERE p.id = ?");
    $stmt->execute([$id_pemesanan]);
    $pemesanan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika pemesanan tidak ditemukan, redirect ke halaman daftar pemesanan
    if (!$pemesanan) {
        $_SESSION['flash_message'] = "Pemesanan tidak ditemukan";
        $_SESSION['flash_type'] = "red";
        
        // Pastikan semua output buffer kosong sebelum redirect
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header("Location: " . ADMIN_URL . "pemesanan/index.php");
        exit;
    }

    // Hitung durasi sewa
    $tanggal_mulai = new DateTime($pemesanan['tanggal_mulai']);
    $tanggal_selesai = new DateTime($pemesanan['tanggal_selesai']);
    $durasi = $tanggal_mulai->diff($tanggal_selesai)->days;

} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    
    // Pastikan semua output buffer kosong sebelum redirect
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header("Location: " . ADMIN_URL . "pemesanan/index.php");
    exit;
}

// Fungsi untuk mendapatkan label status
function getStatusLabel($status_pemesanan) {    
    switch ($status_pemesanan) {        
        case 'menunggu':            
            return '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">Menunggu Pembayaran</span>';        
        case 'dibayar':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Dibayar</span>';        
        case 'dikonfirmasi':            
            return '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">Dikonfirmasi</span>';        
        case 'berjalan':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Berjalan</span>';        
        case 'selesai':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Selesai</span>';        
        case 'dibatalkan':            
            return '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">Dibatalkan</span>';        
        default:            
            return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">' . ucfirst(str_replace('_', ' ', $status_pemesanan ?? '')) . '</span>';
    }
}

// Fungsi untuk mengecek apakah status bisa diubah
function isStatusAllowed($current_status, $new_status) {
    // Daftar transisi status yang diizinkan    
    $allowed_transitions = [
        'menunggu' => ['dibayar', 'dikonfirmasi', 'dibatalkan'],
        'dibayar' => ['dikonfirmasi', 'berjalan', 'dibatalkan'],
        'dikonfirmasi' => ['berjalan', 'dibatalkan'],
        'berjalan' => ['selesai', 'dibatalkan'],
        'selesai' => [],
        'dibatalkan' => [],
    ];
    
    // Jika status saat ini tidak ada dalam daftar, tampilkan semua status yang mungkin
    if (!isset($allowed_transitions[$current_status])) {
        $all_possible_status = ['menunggu', 'dibayar', 'dikonfirmasi', 'berjalan', 'selesai', 'dibatalkan'];
        return in_array($new_status, $all_possible_status);
    }
    
    return in_array($new_status, $allowed_transitions[$current_status]);
}
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-clipboard-list mr-3 text-primary-600"></i> Detail Pemesanan
        </h1>
        <p class="text-sm text-gray-600">Kode: <?= $pemesanan['kode_pemesanan'] ?></p>
    </div>
    <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali
    </a>
</div>

<!-- Status dan Update Status -->
<div class="bg-white rounded-lg shadow-sm mb-6 p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
        <div class="mb-4 md:mb-0">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">Status Pemesanan</h2>
            <div class="flex items-center">
                <?= getStatusLabel($pemesanan['status_pemesanan']) ?>
                <span class="ml-2 text-sm text-gray-500">
                    Terakhir diupdate: <?= date('d F Y H:i', strtotime($pemesanan['updated_at'])) ?>
                </span>
            </div>
        </div>
        
        <?php if ($pemesanan['status_pemesanan'] !== 'selesai' && $pemesanan['status_pemesanan'] !== 'dibatalkan'): ?>
            <button type="button" id="update-status-btn" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-edit mr-2"></i> Update Status
            </button>
        <?php endif; ?>
    </div>
    
    <!-- Form Update Status (tersembunyi secara default) -->
    <div id="update-status-form" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
        <form action="" method="POST">
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status Baru</label>
                <?php 
                // Cek apakah ada opsi status yang tersedia
                $has_options = false;
                foreach (['dibayar', 'dikonfirmasi', 'berjalan', 'selesai', 'dibatalkan'] as $status) {
                    if (isStatusAllowed($pemesanan['status_pemesanan'], $status)) {
                        $has_options = true;
                        break;
                    }
                }
                ?>
                
                <?php if (!$has_options): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 text-yellow-700 mb-4">
                    <p class="font-medium">Tidak ada status yang dapat dipilih</p>
                    <p class="text-sm mt-1">Status pemesanan saat ini sudah final atau tidak dapat diubah.</p>
                </div>
                <?php endif; ?>
                
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" required <?= !$has_options ? 'disabled' : '' ?>>
                    <option value="">-- Pilih Status --</option>
                    
                    <?php if (isStatusAllowed($pemesanan['status_pemesanan'], 'dibayar')): ?>
                        <option value="dibayar">Dibayar</option>
                    <?php endif; ?>
                    
                    <?php if (isStatusAllowed($pemesanan['status_pemesanan'], 'dikonfirmasi')): ?>
                        <option value="dikonfirmasi">Dikonfirmasi</option>
                    <?php endif; ?>
                    
                    <?php if (isStatusAllowed($pemesanan['status_pemesanan'], 'berjalan')): ?>
                        <option value="berjalan">Berjalan</option>
                    <?php endif; ?>
                    
                    <?php if (isStatusAllowed($pemesanan['status_pemesanan'], 'selesai')): ?>
                        <option value="selesai">Selesai</option>
                    <?php endif; ?>
                    
                    <?php if (isStatusAllowed($pemesanan['status_pemesanan'], 'dibatalkan')): ?>
                        <option value="dibatalkan">Dibatalkan</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="catatan_admin" class="block text-sm font-medium text-gray-700 mb-1">Catatan Admin (Opsional)</label>
                <textarea id="catatan_admin" name="catatan_admin" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Tambahkan catatan atau alasan perubahan status"><?= htmlspecialchars($pemesanan['catatan_admin'] ?? '') ?></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" id="cancel-update-btn" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition duration-200" <?= !$has_options ? 'disabled' : '' ?>>
                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Informasi Detail -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
    <!-- Detail Mobil -->
    <div class="md:col-span-7 bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Mobil</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Foto Mobil -->
                <div class="bg-gray-100 rounded-lg overflow-hidden h-48">
                    <?php if (!empty($pemesanan['foto_mobil']) && file_exists('../../assets/uploads/mobil/' . $pemesanan['foto_mobil'])): ?>
                        <img src="<?= ASSETS_URL ?>uploads/mobil/<?= $pemesanan['foto_mobil'] ?>" alt="<?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-gray-200">
                            <i class="fas fa-car text-gray-400 text-5xl"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info Mobil -->
                <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2"><?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?></h3>
                    <p class="text-gray-600 mb-3">Plat: <span class="font-semibold"><?= $pemesanan['nomor_plat'] ?></span></p>
                    
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <p class="text-gray-600">Tahun: <span class="font-semibold"><?= $pemesanan['tahun_produksi'] ?></span></p>
                            <p class="text-gray-600">Transmisi: <span class="font-semibold"><?= ucfirst($pemesanan['transmisi']) ?></span></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Kapasitas: <span class="font-semibold"><?= $pemesanan['kapasitas'] ?> Orang</span></p>
                            <p class="text-gray-600">BBM: <span class="font-semibold"><?= ucfirst($pemesanan['bahan_bakar']) ?></span></p>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-gray-600">Kategori: <span class="font-semibold"><?= $pemesanan['nama_kategori'] ?? 'Uncategorized' ?></span></p>
                        <p class="text-gray-600">Harga/hari: <span class="font-semibold">Rp <?= number_format($pemesanan['harga_sewa_per_hari'], 0, ',', '.') ?></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Penyewa -->
    <div class="md:col-span-5 bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penyewa</h2>
            
            <div class="mb-4">
                <p class="text-gray-600 mb-1">Nama: <span class="font-semibold"><?= $pemesanan['nama_user'] ?></span></p>
                <p class="text-gray-600 mb-1">Email: <span class="font-semibold"><?= $pemesanan['email'] ?></span></p>
                <p class="text-gray-600">Telepon: <span class="font-semibold"><?= $pemesanan['telepon'] ?></span></p>
            </div>
            
            <div class="flex justify-end">
                <a href="<?= ADMIN_URL ?>user/detail.php?id=<?= $pemesanan['user_id'] ?>" class="text-primary-600 hover:text-primary-700">
                    <i class="fas fa-user mr-1"></i> Lihat Profil User
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Detail Pemesanan dan Pembayaran -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-6">
    <!-- Detail Pemesanan -->
    <div class="md:col-span-7 bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Pemesanan</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-gray-600 mb-1">Kode Pemesanan: <span class="font-semibold"><?= $pemesanan['kode_pemesanan'] ?></span></p>
                    <p class="text-gray-600 mb-1">Tanggal Pesan: <span class="font-semibold"><?= date('d F Y H:i', strtotime($pemesanan['created_at'])) ?></span></p>
                </div>
                <div>
                    <p class="text-gray-600 mb-1">Tanggal Mulai: <span class="font-semibold"><?= date('d F Y', strtotime($pemesanan['tanggal_mulai'])) ?></span></p>
                    <p class="text-gray-600 mb-1">Tanggal Selesai: <span class="font-semibold"><?= date('d F Y', strtotime($pemesanan['tanggal_selesai'])) ?></span></p>
                    <p class="text-gray-600">Durasi: <span class="font-semibold"><?= $durasi ?> hari</span></p>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-4 mb-4">
                <h3 class="text-md font-semibold text-gray-800 mb-2">Rincian Biaya</h3>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Harga Sewa per Hari:</span>
                    <span class="text-gray-800">Rp <?= number_format($pemesanan['harga_sewa_per_hari'], 0, ',', '.') ?></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Durasi Sewa:</span>
                    <span class="text-gray-800"><?= $durasi ?> hari</span>
                </div>
                <div class="flex justify-between font-bold text-lg border-t border-gray-100 pt-2 mt-2">
                    <span class="text-gray-800">Total Biaya:</span>
                    <span class="text-blue-600">Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?></span>
                </div>
            </div>
            
            <?php if (!empty($pemesanan['catatan'])): ?>
            <div class="border-t border-gray-100 pt-4 mb-4">
                <h3 class="text-md font-semibold text-gray-800 mb-2">Catatan Penyewa</h3>
                <p class="text-gray-600 bg-gray-50 p-3 rounded-lg"><?= nl2br(htmlspecialchars($pemesanan['catatan'])) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($pemesanan['catatan_admin'])): ?>
            <div class="border-t border-gray-100 pt-4">
                <h3 class="text-md font-semibold text-gray-800 mb-2">Catatan Admin</h3>
                <p class="text-gray-600 bg-yellow-50 p-3 rounded-lg"><?= nl2br(htmlspecialchars($pemesanan['catatan_admin'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Pembayaran -->
    <div class="md:col-span-5 bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Pembayaran</h2>
            
            <?php if (empty($pemesanan['metode_pembayaran'])): ?>
                <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg">
                    <p class="font-medium">Belum ada informasi pembayaran</p>
                    <p class="text-sm mt-1">Penyewa belum melakukan pembayaran.</p>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <p class="text-gray-600 mb-1">Metode Pembayaran: <span class="font-semibold"><?php
                        if ($pemesanan['metode_pembayaran'] === 'transfer_bank') {
                            echo 'Transfer Bank';
                        } elseif ($pemesanan['metode_pembayaran'] === 'tunai') {
                            echo 'Bayar di Tempat';
                        } elseif ($pemesanan['metode_pembayaran'] === 'e-wallet') {
                            echo 'E-Wallet';
                        } else {
                            echo ucfirst(str_replace('_', ' ', $pemesanan['metode_pembayaran']));
                        }
                    ?></span></p>
                    
                    <?php if ($pemesanan['metode_pembayaran'] === 'transfer_bank'): ?>
                        <div class="mt-4">
                            <h3 class="text-md font-semibold text-gray-800 mb-2">Bukti Pembayaran</h3>
                            
                            <?php if (empty($pemesanan['bukti_pembayaran'])): ?>
                                <p class="text-yellow-600">Bukti pembayaran belum diunggah.</p>
                            <?php else: ?>
                                <?php 
                                $file_ext = pathinfo($pemesanan['bukti_pembayaran'], PATHINFO_EXTENSION);
                                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])): 
                                ?>
                                    <a href="<?= ASSETS_URL ?>uploads/pembayaran/<?= $pemesanan['bukti_pembayaran'] ?>" target="_blank" class="block">
                                        <img src="<?= ASSETS_URL ?>uploads/pembayaran/<?= $pemesanan['bukti_pembayaran'] ?>" alt="Bukti Pembayaran" class="max-w-full h-auto rounded-lg border border-gray-200 hover:border-primary-500 transition-all">
                                    </a>
                                    <p class="text-sm text-gray-500 mt-2">Klik gambar untuk memperbesar</p>
                                <?php else: ?>
                                    <a href="<?= ASSETS_URL ?>uploads/pembayaran/<?= $pemesanan['bukti_pembayaran'] ?>" target="_blank" class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
                                        <i class="fas fa-file-pdf mr-2"></i> Lihat Bukti Pembayaran
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($pemesanan['status_pemesanan'] === 'dikonfirmasi'): ?>
                    <div class="mt-4 bg-blue-50 p-4 rounded-lg">
                        <p class="text-blue-800 font-medium mb-2">Pembayaran menunggu konfirmasi</p>
                        <p class="text-blue-700 text-sm">Silakan periksa bukti pembayaran dan konfirmasi dengan mengubah status menjadi "Berjalan".</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($pemesanan['status_pemesanan'] === 'dibayar'): ?>
                    <div class="mt-4 bg-green-50 p-4 rounded-lg">
                        <p class="text-green-800 font-medium mb-2">Pembayaran telah diterima</p>
                        <p class="text-green-700 text-sm">Pembayaran sudah diterima. Silakan konfirmasi pemesanan dengan mengubah status menjadi "Dikonfirmasi" atau "Berjalan".</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Footer Actions -->
<div class="flex justify-end mt-6">
    <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle form update status
    const updateBtn = document.getElementById('update-status-btn');
    const cancelBtn = document.getElementById('cancel-update-btn');
    const updateForm = document.getElementById('update-status-form');
    
    if (updateBtn) {
        updateBtn.addEventListener('click', function() {
            updateForm.classList.remove('hidden');
            updateBtn.classList.add('hidden');
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            updateForm.classList.add('hidden');
            updateBtn.classList.remove('hidden');
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 