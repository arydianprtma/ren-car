<?php
/**
 * Manajemen User - Admin Panel
 */
require_once '../includes/auth_check.php';
require_once '../includes/header.php';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Tambahkan di awal file PHP
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    
    try {
        // Cek apakah user yang akan dihapus adalah admin
        $checkAdminStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $checkAdminStmt->execute([$userId]);
        $userRole = $checkAdminStmt->fetchColumn();
        
        if ($userRole === 'admin') {
            // Jangan izinkan menghapus admin
            setFlashMessage("Tidak dapat menghapus akun admin.", "red");
            header("Location: " . ADMIN_URL . "user/index.php");
            exit;
        }
        
        // Cek apakah ada pemesanan terkait user ini
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM pemesanan WHERE user_id = ?");
        $checkStmt->execute([$userId]);
        $hasOrders = $checkStmt->fetchColumn() > 0;
        
        if ($hasOrders) {
            // User memiliki pemesanan, jangan hapus permanen, update status saja
            $stmt = $conn->prepare("UPDATE users SET status = 'nonaktif' WHERE id = ?");
            $stmt->execute([$userId]);
            setFlashMessage("User tidak dapat dihapus karena memiliki riwayat pemesanan. Status user telah diubah menjadi nonaktif.", "yellow");
        } else {
            // User tidak memiliki pemesanan, bisa dihapus permanen
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            setFlashMessage("User berhasil dihapus.", "green");
        }
        
        // Redirect kembali ke halaman daftar user
        header("Location: " . ADMIN_URL . "user/index.php");
        exit;
    } catch (PDOException $e) {
        setFlashMessage("Terjadi kesalahan: " . $e->getMessage(), "red");
    }
}

// Ambil parameter pencarian & filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Item per halaman
$offset = ($page - 1) * $limit;

// Siapkan query dasar
$sql = "SELECT * FROM users WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM users WHERE 1=1";
$params = [];

// Tambahkan kondisi pencarian jika ada
if (!empty($search)) {
    $sql .= " AND (nama LIKE ? OR username LIKE ? OR email LIKE ? OR no_telp LIKE ? OR no_ktp LIKE ?)";
    $countSql .= " AND (nama LIKE ? OR username LIKE ? OR email LIKE ? OR no_telp LIKE ? OR no_ktp LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

// Tambahkan filter status jika dipilih
if (!empty($status)) {
    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";
    $params[] = $status;
}

// Tambahkan pengurutan dan batasan
$sql .= " ORDER BY created_at DESC LIMIT $offset, $limit";

// Eksekusi query
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total items
$stmt = $conn->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800 flex items-center">
        <i class="fas fa-users mr-3 text-primary-600"></i> Manajemen User
    </h1>
</div>

<!-- Filter dan Pencarian -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-wrap items-end gap-4">
        <div class="w-full md:w-1/3">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari User</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama, username, email, no. telp, KTP..." class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        
        <div class="w-full md:w-1/4">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>
        
        <div class="flex items-center space-x-2">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
                <i class="fas fa-search mr-2"></i> Filter
            </button>
            <a href="<?= ADMIN_URL ?>user/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
                <i class="fas fa-sync-alt mr-2"></i> Reset
                </a>
        </div>
    </form>
</div>

<!-- Daftar User -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nama
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email / Username
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Telepon
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tgl Daftar
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada data user yang ditemukan
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                        <?php if (!empty($user['foto_ktp'])): ?>
                                            <img class="h-10 w-10 rounded-full object-cover" src="<?= ASSETS_URL ?>uploads/ktp/<?= $user['foto_ktp'] ?>" alt="KTP <?= $user['nama'] ?>">
                                        <?php else: ?>
                                            <i class="fas fa-user text-gray-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?= $user['nama'] ?></div>
                                        <div class="text-sm text-gray-500"><?= $user['no_ktp'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= $user['email'] ?></div>
                                <div class="text-sm text-gray-500"><?= $user['username'] ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $user['no_telp'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= ADMIN_URL ?>user/detail.php?id=<?= $user['id'] ?>" class="text-primary-600 hover:text-primary-900 mr-3">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                <?php if (($user['role'] ?? '') !== 'admin'): ?>
                                <a href="#" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama']) ?>')" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash-alt"></i> Hapus
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Menampilkan <?= min($offset + 1, $totalItems) ?> - <?= min($offset + $limit, $totalItems) ?> dari <?= $totalItems ?> data
                </div>
                <div class="flex space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-md bg-gray-100 border border-gray-300 text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, min($page - 2, $totalPages - 4));
                    $endPage = min($totalPages, max($page + 2, 5));
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3 py-1 rounded-md bg-primary-600 text-white">
                                <?= $i ?>
                            </span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-500 hover:bg-gray-50">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-md bg-gray-100 border border-gray-300 text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <div class="mb-4 text-center">
            <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-3"></i>
            <h3 class="text-lg font-bold text-gray-800">Konfirmasi Hapus</h3>
            <p class="text-gray-600" id="deleteConfirmationText">Apakah Anda yakin ingin menghapus user ini?</p>
            <div class="mt-2 text-sm text-gray-500">
                <p>Jika user memiliki riwayat pemesanan, user akan dinonaktifkan dan tidak dihapus permanen.</p>
            </div>
        </div>
        <div class="flex justify-end space-x-3">
            <button id="cancelDelete" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg transition duration-200">
                Batal
            </button>
            <form id="deleteForm" method="POST" class="inline">
                <input type="hidden" name="user_id" id="deleteUserId">
                <input type="hidden" name="delete_user" value="1">
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200">
                    Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId, userName) {
    // Set ID user pada form
    document.getElementById('deleteUserId').value = userId;
    
    // Set pesan konfirmasi
    document.getElementById('deleteConfirmationText').textContent = 
        `Apakah Anda yakin ingin menghapus user "${userName}"?`;
    
    // Tampilkan modal
    document.getElementById('deleteModal').classList.remove('hidden');
}

// Tutup modal saat tombol batal diklik
document.getElementById('cancelDelete').addEventListener('click', function() {
    document.getElementById('deleteModal').classList.add('hidden');
});

// Tutup modal saat user mengklik di luar modal
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 