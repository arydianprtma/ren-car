<?php
/**
 * Detail User - Admin Panel
 */
ob_start(); // Tambahkan output buffering di paling awal
require_once '../includes/auth_check.php';
require_once '../includes/header.php';

// Periksa apakah parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "ID User tidak valid";
    $_SESSION['flash_type'] = "red";
    
    // Pastikan semua output buffer kosong sebelum redirect
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header("Location: " . ADMIN_URL . "user/index.php");
    exit;
}

$id_user = $_GET['id'];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil detail user
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika user tidak ditemukan, redirect ke halaman daftar user
    if (!$user) {
        $_SESSION['flash_message'] = "User tidak ditemukan";
        $_SESSION['flash_type'] = "red";
        
        // Pastikan semua output buffer kosong sebelum redirect
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header("Location: " . ADMIN_URL . "user/index.php");
        exit;
    }
    
    // Ambil daftar pemesanan user
    $stmt = $conn->prepare("SELECT p.*, m.merk, m.model 
                           FROM pemesanan p
                           JOIN mobil m ON p.mobil_id = m.id
                           WHERE p.user_id = ?
                           ORDER BY p.created_at DESC");
    $stmt->execute([$id_user]);
    $pemesananList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    
    // Pastikan semua output buffer kosong sebelum redirect
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header("Location: " . ADMIN_URL . "user/index.php");
    exit;
}

// Fungsi untuk mendapatkan label status
function getStatusLabel($status) {
    switch ($status) {
        case 'menunggu':
            return '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">Menunggu Pembayaran</span>';
        case 'dikonfirmasi':
            return '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">Menunggu Konfirmasi</span>';
        case 'berjalan':
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Berjalan</span>';
        case 'selesai':
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Selesai</span>';
        case 'dibatalkan':
            return '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">Dibatalkan</span>';
                default:            return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">' . ucfirst(str_replace('_', ' ', $status ?? '')) . '</span>';
    }
}

// Proses update status user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $id_user]);
        
        $_SESSION['flash_message'] = "Status user berhasil diperbarui";
        $_SESSION['flash_type'] = "green";
        
        // Pastikan semua output buffer kosong sebelum redirect
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Redirect ke halaman ini untuk refresh data
        header("Location: " . ADMIN_URL . "user/detail.php?id=" . $id_user);
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Gagal memperbarui status: " . $e->getMessage();
        $_SESSION['flash_type'] = "red";
    }
}
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-user mr-3 text-primary-600"></i> Detail User
        </h1>
        <p class="text-sm text-gray-600">ID: <?= $user['id'] ?></p>
    </div>
    <a href="<?= ADMIN_URL ?>user/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali
    </a>
</div>

<!-- User Profile Card -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
    <!-- Profil User -->
    <div class="md:col-span-5 bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Informasi Pribadi</h2>
                
                <div class="<?= $user['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> px-3 py-1 rounded-full text-sm font-medium">
                    <?= ucfirst($user['status']) ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 gap-4">
                <div class="flex flex-col md:flex-row md:items-center">
                    <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-4xl mb-4 md:mb-0 md:mr-6">
                        <?php if (!empty($user['foto_ktp'])): ?>
                            <img src="<?= ASSETS_URL ?>uploads/ktp/<?= $user['foto_ktp'] ?>" alt="Foto KTP" class="w-full h-full rounded-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-1"><?= $user['nama'] ?></h3>
                        <p class="text-gray-600">Username: <?= $user['username'] ?></p>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-gray-600 mb-2">Email: <span class="font-medium"><?= $user['email'] ?></span></p>
                    <p class="text-gray-600 mb-2">No. Telepon: <span class="font-medium"><?= $user['no_telp'] ?></span></p>
                    <p class="text-gray-600 mb-2">No. KTP: <span class="font-medium"><?= $user['no_ktp'] ?></span></p>
                    <p class="text-gray-600">Alamat:</p>
                    <p class="text-gray-800 bg-gray-50 p-3 rounded-lg mt-1"><?= nl2br(htmlspecialchars($user['alamat'])) ?></p>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-gray-600 mb-2">Tanggal Registrasi: <span class="font-medium"><?= date('d F Y H:i', strtotime($user['created_at'])) ?></span></p>
                    <p class="text-gray-600">Terakhir Diperbarui: <span class="font-medium"><?= date('d F Y H:i', strtotime($user['updated_at'])) ?></span></p>
                </div>
            </div>
            
            <!-- Form Update Status -->
            <div class="mt-6 pt-6 border-t border-gray-100">
                <h3 class="text-md font-semibold text-gray-800 mb-3">Update Status User</h3>
                
                <form action="" method="POST">
                    <div class="mb-4">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                            <option value="aktif" <?= $user['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $user['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Riwayat Pemesanan -->
    <div class="md:col-span-7 bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Riwayat Pemesanan</h2>
            
            <?php if (empty($pemesananList)): ?>
                <div class="bg-gray-50 rounded-lg p-6 text-center">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center text-gray-400 mx-auto mb-4">
                        <i class="fas fa-calendar-alt text-2xl"></i>
                    </div>
                    <p class="text-gray-600">User ini belum memiliki riwayat pemesanan</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mobil</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pemesananList as $index => $pemesanan): ?>
                                <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $pemesanan['kode_pemesanan'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= date('d/m/Y', strtotime($pemesanan['tanggal_mulai'])) ?> - <?= date('d/m/Y', strtotime($pemesanan['tanggal_selesai'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?= getStatusLabel($pemesanan['status_pemesanan']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="<?= ADMIN_URL ?>pemesanan/detail.php?id=<?= $pemesanan['id'] ?>" class="text-primary-600 hover:text-primary-900 mr-3">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tombol Kembali -->
<div class="flex justify-end mt-6">
    <a href="<?= ADMIN_URL ?>user/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar User
    </a>
</div>

<?php require_once '../includes/footer.php'; ?> 