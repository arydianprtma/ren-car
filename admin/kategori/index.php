<?php
/**
 * Manajemen Kategori Mobil - Admin Panel
 */
require_once '../includes/auth_check.php';
require_once '../includes/header.php';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Proses hapus kategori
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Periksa apakah kategori sedang digunakan
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM mobil WHERE kategori_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count > 0) {
        $_SESSION['flash_message'] = 'Kategori ini tidak bisa dihapus karena sedang digunakan oleh ' . $count . ' mobil!';
        $_SESSION['flash_type'] = 'red';
    } else {
        // Hapus kategori
        $stmt = $conn->prepare("DELETE FROM kategori_mobil WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            $_SESSION['flash_message'] = 'Kategori berhasil dihapus!';
            $_SESSION['flash_type'] = 'green';
        } else {
            $_SESSION['flash_message'] = 'Gagal menghapus kategori!';
            $_SESSION['flash_type'] = 'red';
        }
    }
    
    // Redirect kembali ke daftar kategori
    header("Location: " . ADMIN_URL . "kategori/index.php");
    exit;
}

// Ambil parameter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Buat query dasar
$sql = "SELECT k.*, (SELECT COUNT(*) FROM mobil WHERE kategori_id = k.id) AS jumlah_mobil 
        FROM kategori_mobil k 
        WHERE 1=1";
$params = [];

// Tambahkan filter ke query
if (!empty($search)) {
    $sql .= " AND (k.nama_kategori LIKE ? OR k.deskripsi LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Urutkan hasil
$sql .= " ORDER BY k.nama_kategori ASC";

// Eksekusi query
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$kategoriList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-tags mr-3 text-primary-600"></i> Kategori Mobil
        </h1>
        <p class="text-sm text-gray-600">Kelola kategori mobil yang tersedia untuk disewa</p>
    </div>
    <a href="<?= ADMIN_URL ?>kategori/tambah.php" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-plus mr-2"></i> Tambah Kategori
    </a>
</div>

<!-- Filter & Search -->
<div class="bg-white rounded-lg shadow-sm mb-6 p-4">
    <form action="" method="GET" class="flex items-end gap-4">
        <div class="w-full md:w-1/4">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama kategori..." class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        
        <div class="flex items-center space-x-2">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200">
                <i class="fas fa-search mr-2"></i> Cari
            </button>
            
            <a href="<?= ADMIN_URL ?>kategori/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200">
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

<!-- Data Kategori -->
<div id="content" class="hidden bg-white rounded-lg shadow-sm overflow-hidden">
    <?php if (empty($kategoriList)): ?>
    <div class="text-center py-8">
        <div class="w-20 h-20 mx-auto mb-3 bg-gray-200 rounded-full flex items-center justify-center text-gray-400">
            <i class="fas fa-tags text-3xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-800 mb-1">Belum ada data kategori</h3>
        <p class="text-gray-500 mb-3">Tidak ada data kategori yang ditemukan</p>
        <a href="<?= ADMIN_URL ?>kategori/tambah.php" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
            <i class="fas fa-plus mr-2"></i> Tambah Kategori Baru
        </a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Mobil</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($kategoriList as $kategori): ?>
                <tr class="hover:bg-gray-50 transition duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= $kategori['nama_kategori'] ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-500 max-w-md truncate">
                            <?= $kategori['deskripsi'] ? substr($kategori['deskripsi'], 0, 100) . (strlen($kategori['deskripsi']) > 100 ? '...' : '') : '-' ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?= $kategori['jumlah_mobil'] ?> Mobil
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex justify-center space-x-3">
                            <a href="<?= ADMIN_URL ?>kategori/edit.php?id=<?= $kategori['id'] ?>" class="text-primary-600 hover:text-primary-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?= ADMIN_URL ?>mobil/index.php?kategori_id=<?= $kategori['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Lihat Mobil">
                                <i class="fas fa-car"></i>
                            </a>
                            <?php if ($kategori['jumlah_mobil'] == 0): ?>
                            <a href="#" onclick="confirmDelete(<?= $kategori['id'] ?>, '<?= htmlspecialchars($kategori['nama_kategori']) ?>')" class="text-red-600 hover:text-red-900" title="Hapus">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                            <?php else: ?>
                            <span class="text-gray-400 cursor-not-allowed" title="Tidak dapat dihapus karena memiliki mobil terkait">
                                <i class="fas fa-trash-alt"></i>
                            </span>
                            <?php endif; ?>
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
            <p class="text-gray-600" id="deleteConfirmationText">Apakah Anda yakin ingin menghapus kategori ini?</p>
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

function confirmDelete(id, kategoriName) {
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteConfirmationText = document.getElementById('deleteConfirmationText');
    
    deleteConfirmationText.textContent = `Apakah Anda yakin ingin menghapus kategori "${kategoriName}"?`;
    confirmDeleteBtn.href = `<?= ADMIN_URL ?>kategori/index.php?action=delete&id=${id}`;
    
    deleteModal.classList.remove('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?> 