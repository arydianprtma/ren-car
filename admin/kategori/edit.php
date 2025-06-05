<?php
/**
 * Edit Kategori Mobil - Admin Panel
 */
require_once '../includes/auth_check.php';

// Cek apakah ID kategori ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID kategori tidak valid!';
    $_SESSION['flash_type'] = 'red';
    header("Location: " . ADMIN_URL . "kategori/index.php");
    exit;
}

$id = $_GET['id'];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil data kategori berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM kategori_mobil WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    $_SESSION['flash_message'] = 'Kategori tidak ditemukan!';
    $_SESSION['flash_type'] = 'red';
    header("Location: " . ADMIN_URL . "kategori/index.php");
    exit;
}

$kategori = $stmt->fetch(PDO::FETCH_ASSOC);

// Inisialisasi variabel
$nama_kategori = $kategori['nama_kategori'];
$deskripsi = $kategori['deskripsi'];
$errors = [];

// Proses form jika ada pengiriman POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data form
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    // Validasi input
    if (empty($nama_kategori)) {
        $errors['nama_kategori'] = 'Nama kategori tidak boleh kosong';
    }
    
    // Cek apakah nama kategori sudah ada (selain kategori ini sendiri)
    $stmt = $conn->prepare("SELECT id FROM kategori_mobil WHERE nama_kategori = ? AND id != ?");
    $stmt->execute([$nama_kategori, $id]);
    if ($stmt->rowCount() > 0) {
        $errors['nama_kategori'] = 'Nama kategori sudah ada, silakan gunakan nama lain';
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE kategori_mobil SET nama_kategori = ?, deskripsi = ?, updated_at = NOW() WHERE id = ?");
        
        if ($stmt->execute([$nama_kategori, $deskripsi, $id])) {
            $_SESSION['flash_message'] = 'Kategori mobil berhasil diperbarui!';
            $_SESSION['flash_type'] = 'green';
            
            // Redirect ke halaman daftar kategori
            header("Location: " . ADMIN_URL . "kategori/index.php");
            exit;
        } else {
            $errors['db'] = 'Gagal memperbarui data kategori';
        }
    }
}

// Sisipkan header setelah semua operasi redirect
require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-edit mr-3 text-primary-600"></i> Edit Kategori Mobil
        </h1>
        <p class="text-sm text-gray-600">Perbarui informasi kategori mobil</p>
    </div>
    <a href="<?= ADMIN_URL ?>kategori/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali
    </a>
</div>

<!-- Form Edit Kategori -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <form action="" method="POST" class="p-6">
        <?php if (isset($errors['db'])): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg">
            <p><?= $errors['db'] ?></p>
        </div>
        <?php endif; ?>
        
        <div class="mb-4">
            <label for="nama_kategori" class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori <span class="text-red-600">*</span></label>
            <input type="text" id="nama_kategori" name="nama_kategori" value="<?= htmlspecialchars($nama_kategori) ?>" class="w-full px-3 py-2 border <?= isset($errors['nama_kategori']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Masukkan nama kategori">
            <?php if (isset($errors['nama_kategori'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= $errors['nama_kategori'] ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-6">
            <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Masukkan deskripsi kategori (opsional)"><?= htmlspecialchars($deskripsi) ?></textarea>
        </div>
        
        <div class="flex justify-end space-x-2">
            <a href="<?= ADMIN_URL ?>kategori/index.php" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition duration-200">
                Batal
            </a>
            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition duration-200">
                <i class="fas fa-save mr-2"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<!-- Informasi Kategori -->
<div class="mt-6 bg-blue-50 rounded-lg p-4 border border-blue-100">
    <h3 class="text-lg font-semibold text-blue-800 mb-2">Informasi Kategori</h3>
    <div class="text-sm text-blue-700">
        <p class="mb-1"><strong>ID Kategori:</strong> <?= htmlspecialchars($id) ?></p>
        <p class="mb-1"><strong>Dibuat pada:</strong> <?= date('d/m/Y H:i', strtotime($kategori['created_at'])) ?></p>
        <?php if (!empty($kategori['updated_at'])): ?>
        <p><strong>Terakhir diupdate:</strong> <?= date('d/m/Y H:i', strtotime($kategori['updated_at'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto focus pada field nama kategori
    document.getElementById('nama_kategori').focus();
});
</script>

<?php require_once '../includes/footer.php'; ?> 