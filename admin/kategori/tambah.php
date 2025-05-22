<?php
/**
 * Tambah Kategori Mobil - Admin Panel
 */
require_once '../includes/auth_check.php';

// Inisialisasi variabel
$nama_kategori = '';
$deskripsi = '';
$errors = [];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Proses form jika ada pengiriman POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data form
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    // Validasi input
    if (empty($nama_kategori)) {
        $errors['nama_kategori'] = 'Nama kategori tidak boleh kosong';
    }
    
    // Cek apakah nama kategori sudah ada
    $stmt = $conn->prepare("SELECT id FROM kategori_mobil WHERE nama_kategori = ?");
    $stmt->execute([$nama_kategori]);
    if ($stmt->rowCount() > 0) {
        $errors['nama_kategori'] = 'Nama kategori sudah ada, silakan gunakan nama lain';
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO kategori_mobil (nama_kategori, deskripsi, created_at) VALUES (?, ?, NOW())");
        
        if ($stmt->execute([$nama_kategori, $deskripsi])) {
            $_SESSION['flash_message'] = 'Kategori mobil berhasil ditambahkan!';
            $_SESSION['flash_type'] = 'green';
            
            // Redirect ke halaman daftar kategori
            header("Location: " . ADMIN_URL . "kategori/index.php");
            exit;
        } else {
            $errors['db'] = 'Gagal menyimpan data kategori';
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
            <i class="fas fa-plus-circle mr-3 text-primary-600"></i> Tambah Kategori Mobil
        </h1>
        <p class="text-sm text-gray-600">Tambahkan kategori mobil baru</p>
    </div>
    <a href="<?= ADMIN_URL ?>kategori/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali
    </a>
</div>

<!-- Form Tambah Kategori -->
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
            <button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition duration-200">
                Reset
            </button>
            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition duration-200">
                <i class="fas fa-save mr-2"></i> Simpan
            </button>
        </div>
    </form>
</div>

<!-- Panduan Tambah Kategori -->
<div class="mt-6 bg-blue-50 rounded-lg p-4 border border-blue-100">
    <h3 class="text-lg font-semibold text-blue-800 mb-2">Panduan Penambahan Kategori</h3>
    <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
        <li>Nama kategori harus unik dan tidak boleh sama dengan kategori yang sudah ada</li>
        <li>Nama kategori sebaiknya singkat dan jelas (Contoh: SUV, MPV, Sedan, dll)</li>
        <li>Deskripsi kategori bersifat opsional, namun sangat disarankan untuk memberikan informasi yang jelas</li>
        <li>Setelah kategori dibuat, Anda dapat menambahkan mobil ke kategori tersebut</li>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto focus pada field nama kategori
    document.getElementById('nama_kategori').focus();
});
</script>

<?php require_once '../includes/footer.php'; ?> 