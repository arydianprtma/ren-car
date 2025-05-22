<?php
/**
 * Manajemen Mobil - Admin Panel
 */
require_once '../includes/auth_check.php';
require_once '../includes/header.php';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil parameter filter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kategori_id = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Proses hapus mobil
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Cek apakah mobil sedang disewa
    $stmt = $conn->prepare("SELECT status FROM mobil WHERE id = ?");
    $stmt->execute([$id]);
    $mobilStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($mobilStatus && $mobilStatus['status'] == 'disewa') {
        $_SESSION['flash_message'] = 'Mobil sedang disewa, tidak dapat dihapus!';
        $_SESSION['flash_type'] = 'red';
    } else {
        // Cek apakah ada pemesanan untuk mobil ini
        $stmt = $conn->prepare("SELECT id FROM pemesanan WHERE mobil_id = ? AND status_pemesanan IN ('menunggu', 'dikonfirmasi', 'berjalan')");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_message'] = 'Mobil memiliki pemesanan aktif, tidak dapat dihapus!';
            $_SESSION['flash_type'] = 'red';
        } else {
            // Ambil data foto mobil sebelum dihapus
            $stmt = $conn->prepare("SELECT foto_mobil FROM mobil WHERE id = ?");
            $stmt->execute([$id]);
            $mobil = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Hapus file foto jika ada
            if (!empty($mobil['foto_mobil'])) {
                $file_path = '../../assets/uploads/mobil/' . $mobil['foto_mobil'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Hapus data mobil
            $stmt = $conn->prepare("DELETE FROM mobil WHERE id = ?");
            if ($stmt->execute([$id])) {
                $_SESSION['flash_message'] = 'Mobil berhasil dihapus!';
                $_SESSION['flash_type'] = 'green';
            } else {
                $_SESSION['flash_message'] = 'Gagal menghapus mobil!';
                $_SESSION['flash_type'] = 'red';
            }
        }
    }
    
    // Redirect kembali ke halaman daftar
    header("Location: " . ADMIN_URL . "mobil/index.php");
    exit;
}

// Ambil daftar kategori untuk filter
$stmt = $conn->query("SELECT * FROM kategori_mobil ORDER BY nama_kategori ASC");
$kategoriList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buat query dasar
$sql = "SELECT m.*, k.nama_kategori FROM mobil m 
        LEFT JOIN kategori_mobil k ON m.kategori_id = k.id WHERE 1=1";
$params = [];

// Tambahkan filter ke query
if (!empty($search)) {
    $sql .= " AND (m.merk LIKE ? OR m.model LIKE ? OR m.nomor_plat LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($kategori_id)) {
    $sql .= " AND m.kategori_id = ?";
    $params[] = $kategori_id;
}

if (!empty($status)) {
    $sql .= " AND m.status = ?";
    $params[] = $status;
}

// Urutkan hasil
$sql .= " ORDER BY m.id DESC";

// Eksekusi query
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$mobilList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-car mr-3 text-primary-600"></i> Manajemen Mobil
        </h1>
        <p class="text-sm text-gray-600">Kelola data mobil yang tersedia untuk disewa</p>
    </div>
    <a href="<?= ADMIN_URL ?>mobil/tambah.php" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-plus mr-2"></i> Tambah Mobil
    </a>
</div>

<!-- Filter & Search -->
<div class="bg-white rounded-lg shadow-sm mb-6 p-4">
    <form action="" method="GET" class="flex flex-wrap items-end gap-4">
        <div class="w-full md:w-1/5">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Merk, model, plat..." class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        
        <div class="w-full md:w-1/5">
            <label for="kategori_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
            <select id="kategori_id" name="kategori_id" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategoriList as $kategori): ?>
                <option value="<?= $kategori['id'] ?>" <?= ($kategori_id == $kategori['id']) ? 'selected' : '' ?>><?= $kategori['nama_kategori'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="w-full md:w-1/5">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Semua Status</option>
                <option value="tersedia" <?= ($status == 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
                <option value="disewa" <?= ($status == 'disewa') ? 'selected' : '' ?>>Sedang Disewa</option>
                <option value="pemeliharaan" <?= ($status == 'pemeliharaan') ? 'selected' : '' ?>>Pemeliharaan</option>
            </select>
        </div>
        
        <div class="flex items-center space-x-2">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200">
                <i class="fas fa-search mr-2"></i> Filter
            </button>
            
            <a href="<?= ADMIN_URL ?>mobil/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200">
                <i class="fas fa-sync-alt mr-2"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Skeleton Loader -->
<div id="skeleton-loader" class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="animate-pulse">
        <div class="h-10 bg-gray-200 w-full mb-4"></div>
        <div class="p-4">
            <?php for ($i = 0; $i < 5; $i++): ?>
            <div class="h-16 bg-gray-200 w-full mb-3 rounded"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Data Mobil -->
<div id="content" class="hidden bg-white rounded-lg shadow-sm overflow-hidden">
    <?php if (empty($mobilList)): ?>
    <div class="text-center py-8">
        <div class="w-20 h-20 mx-auto mb-3 bg-gray-200 rounded-full flex items-center justify-center text-gray-400">
            <i class="fas fa-car-side text-3xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-800 mb-1">Belum ada data mobil</h3>
        <p class="text-gray-500 mb-3">Tidak ada data mobil yang ditemukan</p>
        <a href="<?= ADMIN_URL ?>mobil/tambah.php" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
            <i class="fas fa-plus mr-2"></i> Tambah Mobil Baru
        </a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Info Mobil</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spesifikasi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Sewa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($mobilList as $mobil): ?>
                <tr class="hover:bg-gray-50 transition duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden">
                            <?php if (!empty($mobil['foto_mobil']) && file_exists('../../assets/uploads/mobil/' . $mobil['foto_mobil'])): ?>
                                <img src="<?= BASE_URL ?>assets/uploads/mobil/<?= $mobil['foto_mobil'] ?>" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-400">
                                    <i class="fas fa-car text-3xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900"><?= $mobil['merk'] ?> <?= $mobil['model'] ?></div>
                        <div class="text-sm text-gray-500"><?= $mobil['nomor_plat'] ?> | <?= $mobil['nama_kategori'] ?? 'Tidak Ada Kategori' ?></div>
                        <div class="text-sm text-gray-500">Tahun: <?= $mobil['tahun_produksi'] ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            <div class="flex items-center mb-1">
                                <i class="fas fa-user-friends text-primary-600 mr-2"></i>
                                <span><?= $mobil['kapasitas'] ?> Orang</span>
                            </div>
                            <div class="flex items-center mb-1">
                                <i class="fas fa-gas-pump text-primary-600 mr-2"></i>
                                <span><?= ucfirst($mobil['bahan_bakar']) ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-cog text-primary-600 mr-2"></i>
                                <span><?= ucfirst($mobil['transmisi']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?> / hari
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $statusClasses = [
                            'tersedia' => 'bg-green-100 text-green-800',
                            'disewa' => 'bg-blue-100 text-blue-800',
                            'pemeliharaan' => 'bg-amber-100 text-amber-800'
                        ];
                        $statusClass = $statusClasses[$mobil['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                            <?= ucfirst($mobil['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex justify-center space-x-2">
                            <a href="<?= ADMIN_URL ?>mobil/edit.php?id=<?= $mobil['id'] ?>" class="text-primary-600 hover:text-primary-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?= ADMIN_URL ?>mobil/detail.php?id=<?= $mobil['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="#" onclick="confirmDelete(<?= $mobil['id'] ?>, '<?= $mobil['merk'] ?> <?= $mobil['model'] ?>')" class="text-red-600 hover:text-red-900" title="Hapus">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <div class="mb-4 text-center">
            <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-3"></i>
            <h3 class="text-lg font-bold text-gray-800">Konfirmasi Hapus</h3>
            <p class="text-gray-600" id="deleteConfirmationText">Apakah Anda yakin ingin menghapus data mobil ini?</p>
        </div>
        <div class="flex justify-end space-x-3">
            <button id="cancelDelete" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg transition duration-200">
                Batal
            </button>
            <a href="#" id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200">
                Hapus
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simulasi loading untuk skeleton loader
    setTimeout(function() {
        document.getElementById('skeleton-loader').style.display = 'none';
        document.getElementById('content').classList.remove('hidden');
    }, 1000);
    
    // Modal Delete
    const deleteModal = document.getElementById('deleteModal');
    const cancelDelete = document.getElementById('cancelDelete');
    
    cancelDelete.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });
    
    window.addEventListener('click', function(event) {
        if (event.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
    });
});

function confirmDelete(id, mobilName) {
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteConfirmationText = document.getElementById('deleteConfirmationText');
    
    deleteConfirmationText.textContent = `Apakah Anda yakin ingin menghapus mobil "${mobilName}"?`;
    confirmDeleteBtn.href = `<?= ADMIN_URL ?>mobil/index.php?action=delete&id=${id}`;
    
    deleteModal.classList.remove('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?> 