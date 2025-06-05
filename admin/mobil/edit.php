<?php
/**
 * Edit Mobil - Admin Panel
 */
require_once '../includes/auth_check.php';

// Inisialisasi variabel
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$merk = '';
$model = '';
$nomor_plat = '';
$tahun_produksi = date('Y');
$warna = '';
$kapasitas = 5;
$transmisi = 'manual';
$bahan_bakar = 'bensin';
$kategori_id = '';
$harga_sewa_per_hari = '';
$status = 'tersedia';
$deskripsi = '';
$foto_mobil_lama = '';
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
    $stmt = $conn->prepare("SELECT * FROM mobil WHERE id = ?");
    $stmt->execute([$id]);
    $mobil = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mobil) {
        $_SESSION['flash_message'] = 'Mobil tidak ditemukan';
        $_SESSION['flash_type'] = 'red';
        header("Location: " . ADMIN_URL . "mobil/index.php");
        exit;
    }
    
    // Isi variabel dengan data dari database
    $merk = $mobil['merk'];
    $model = $mobil['model'];
    $nomor_plat = $mobil['nomor_plat'];
    $tahun_produksi = $mobil['tahun_produksi'];
    $warna = $mobil['warna'];
    $kapasitas = $mobil['kapasitas'];
    $transmisi = $mobil['transmisi'];
    $bahan_bakar = $mobil['bahan_bakar'];
    $kategori_id = $mobil['kategori_id'];
    $harga_sewa_per_hari = $mobil['harga_sewa_per_hari'];
    $status = $mobil['status'];
    $deskripsi = $mobil['deskripsi'];
    $foto_mobil_lama = $mobil['foto_mobil'];
    
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'red';
    header("Location: " . ADMIN_URL . "mobil/index.php");
    exit;
}

// Ambil daftar kategori
try {
    $stmt = $conn->query("SELECT id, nama_kategori FROM kategori_mobil ORDER BY nama_kategori ASC");
    $kategoriList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $kategoriList = [];
}

// Proses form jika ada pengiriman POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data form
    $merk = trim($_POST['merk'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $nomor_plat = trim($_POST['nomor_plat'] ?? '');
    $tahun_produksi = $_POST['tahun_produksi'] ?? date('Y');
    $warna = trim($_POST['warna'] ?? '');
    $kapasitas = $_POST['kapasitas'] ?? 5;
    $transmisi = $_POST['transmisi'] ?? 'manual';
    $bahan_bakar = $_POST['bahan_bakar'] ?? 'bensin';
    $kategori_id = $_POST['kategori_id'] ?? '';
    $harga_sewa_per_hari = $_POST['harga_sewa_per_hari'] ?? '';
    $status = $_POST['status'] ?? 'tersedia';
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    // Format harga
    $harga_sewa_per_hari = str_replace('.', '', $harga_sewa_per_hari);
    
    // Validasi input
    if (empty($merk)) {
        $errors['merk'] = 'Merk mobil tidak boleh kosong';
    }
    
    if (empty($model)) {
        $errors['model'] = 'Model mobil tidak boleh kosong';
    }
    
    if (empty($nomor_plat)) {
        $errors['nomor_plat'] = 'Nomor plat tidak boleh kosong';
    } else if ($nomor_plat !== $mobil['nomor_plat']) {
        // Cek apakah nomor plat sudah ada jika diubah
        $stmt = $conn->prepare("SELECT id FROM mobil WHERE nomor_plat = ? AND id != ?");
        $stmt->execute([$nomor_plat, $id]);
        if ($stmt->rowCount() > 0) {
            $errors['nomor_plat'] = 'Nomor plat sudah terdaftar pada mobil lain';
        }
    }
    
    if (empty($warna)) {
        $errors['warna'] = 'Warna mobil tidak boleh kosong';
    }
    
    if (!is_numeric($kapasitas) || $kapasitas < 1) {
        $errors['kapasitas'] = 'Kapasitas penumpang harus berupa angka dan minimal 1';
    }
    
    if (empty($kategori_id)) {
        $errors['kategori_id'] = 'Kategori mobil harus dipilih';
    }
    
    if (empty($harga_sewa_per_hari) || !is_numeric($harga_sewa_per_hari)) {
        $errors['harga_sewa_per_hari'] = 'Harga sewa per hari harus berupa angka';
    }
    
    // Validasi file upload
    $foto_mobil = $foto_mobil_lama; // Default tetap menggunakan foto lama
    $update_foto = false;
    
    if (!empty($_FILES['foto_mobil']['name'])) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['foto_mobil']['type'], $allowed_types)) {
            $errors['foto_mobil'] = 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP';
        } elseif ($_FILES['foto_mobil']['size'] > $max_size) {
            $errors['foto_mobil'] = 'Ukuran file terlalu besar (maksimal 2MB)';
        } else {
            // Generate unique filename
            $extension = pathinfo($_FILES['foto_mobil']['name'], PATHINFO_EXTENSION);
            $foto_mobil = uniqid('mobil_') . '.' . $extension;
            $update_foto = true;
        }
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        try {
            // Mulai transaksi
            $conn->beginTransaction();
            
            // Upload file jika ada
            if ($update_foto) {
                $upload_dir = '../../assets/uploads/mobil/';
                
                // Cek dan buat direktori jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Upload file
                move_uploaded_file($_FILES['foto_mobil']['tmp_name'], $upload_dir . $foto_mobil);
                
                // Hapus foto lama jika ada
                if (!empty($foto_mobil_lama) && file_exists($upload_dir . $foto_mobil_lama)) {
                    unlink($upload_dir . $foto_mobil_lama);
                }
            }
            
            // Update data mobil
            $sql = "UPDATE mobil SET 
                    merk = ?, model = ?, nomor_plat = ?, tahun_produksi = ?, 
                    warna = ?, kapasitas = ?, transmisi = ?, bahan_bakar = ?, 
                    kategori_id = ?, harga_sewa_per_hari = ?, status = ?, 
                    deskripsi = ?, foto_mobil = ?, updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $merk, $model, $nomor_plat, $tahun_produksi, $warna, 
                $kapasitas, $transmisi, $bahan_bakar, $kategori_id, 
                $harga_sewa_per_hari, $status, $deskripsi, $foto_mobil, $id
            ]);
            
            // Commit transaksi
            $conn->commit();
            
            $_SESSION['flash_message'] = 'Mobil berhasil diperbarui!';
            $_SESSION['flash_type'] = 'green';
            
            // Redirect ke halaman daftar mobil
            header("Location: " . ADMIN_URL . "mobil/index.php");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            $conn->rollback();
            
            $errors['db'] = 'Gagal memperbarui data: ' . $e->getMessage();
            
            // Hapus file yang sudah diupload jika ada
            if ($update_foto && !empty($foto_mobil) && file_exists('../../assets/uploads/mobil/' . $foto_mobil)) {
                unlink('../../assets/uploads/mobil/' . $foto_mobil);
            }
        }
    }
}

// Setelah semua pemrosesan dan redirect selesai, baru include header.php
require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-edit mr-3 text-primary-600"></i> Edit Mobil
        </h1>
        <p class="text-sm text-gray-600">Edit data mobil dengan ID: <?= $id ?></p>
    </div>
    <div class="flex space-x-3">
        <a href="<?= ADMIN_URL ?>mobil/detail.php?id=<?= $id ?>" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fas fa-eye mr-2"></i> Lihat Detail
        </a>
        <a href="<?= ADMIN_URL ?>mobil/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Form Edit Mobil -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <form action="" method="POST" enctype="multipart/form-data" class="p-6" autocomplete="off">
        <?php if (isset($errors['db'])): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg">
            <p><?= $errors['db'] ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="merk" class="block text-sm font-medium text-gray-700 mb-1">Merk Mobil <span class="text-red-600">*</span></label>
                <input type="text" id="merk" name="merk" value="<?= htmlspecialchars($merk) ?>" class="w-full px-3 py-2 border <?= isset($errors['merk']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Toyota">
                <?php if (isset($errors['merk'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['merk'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Model Mobil <span class="text-red-600">*</span></label>
                <input type="text" id="model" name="model" value="<?= htmlspecialchars($model) ?>" class="w-full px-3 py-2 border <?= isset($errors['model']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Avanza">
                <?php if (isset($errors['model'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['model'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="nomor_plat" class="block text-sm font-medium text-gray-700 mb-1">Nomor Plat <span class="text-red-600">*</span></label>
                <input type="text" id="nomor_plat" name="nomor_plat" value="<?= htmlspecialchars($nomor_plat) ?>" class="w-full px-3 py-2 border <?= isset($errors['nomor_plat']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: B 1234 ABC">
                <?php if (isset($errors['nomor_plat'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['nomor_plat'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="tahun_produksi" class="block text-sm font-medium text-gray-700 mb-1">Tahun Produksi <span class="text-red-600">*</span></label>
                <select id="tahun_produksi" name="tahun_produksi" class="w-full px-3 py-2 border <?= isset($errors['tahun_produksi']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <?php for ($year = date('Y') + 1; $year >= 2000; $year--): ?>
                    <option value="<?= $year ?>" <?= ($tahun_produksi == $year) ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endfor; ?>
                </select>
                <?php if (isset($errors['tahun_produksi'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['tahun_produksi'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="warna" class="block text-sm font-medium text-gray-700 mb-1">Warna <span class="text-red-600">*</span></label>
                <input type="text" id="warna" name="warna" value="<?= htmlspecialchars($warna) ?>" class="w-full px-3 py-2 border <?= isset($errors['warna']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Putih">
                <?php if (isset($errors['warna'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['warna'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="kapasitas" class="block text-sm font-medium text-gray-700 mb-1">Kapasitas Penumpang <span class="text-red-600">*</span></label>
                <input type="number" id="kapasitas" name="kapasitas" value="<?= $kapasitas ?>" min="1" max="20" class="w-full px-3 py-2 border <?= isset($errors['kapasitas']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                <?php if (isset($errors['kapasitas'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['kapasitas'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="transmisi" class="block text-sm font-medium text-gray-700 mb-1">Transmisi <span class="text-red-600">*</span></label>
                <select id="transmisi" name="transmisi" class="w-full px-3 py-2 border <?= isset($errors['transmisi']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <option value="manual" <?= ($transmisi == 'manual') ? 'selected' : '' ?>>Manual</option>
                    <option value="otomatis" <?= ($transmisi == 'otomatis') ? 'selected' : '' ?>>Otomatis</option>
                </select>
                <?php if (isset($errors['transmisi'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['transmisi'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="bahan_bakar" class="block text-sm font-medium text-gray-700 mb-1">Bahan Bakar <span class="text-red-600">*</span></label>
                <select id="bahan_bakar" name="bahan_bakar" class="w-full px-3 py-2 border <?= isset($errors['bahan_bakar']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <option value="bensin" <?= ($bahan_bakar == 'bensin') ? 'selected' : '' ?>>Bensin</option>
                    <option value="diesel" <?= ($bahan_bakar == 'diesel') ? 'selected' : '' ?>>Diesel</option>
                    <option value="listrik" <?= ($bahan_bakar == 'listrik') ? 'selected' : '' ?>>Listrik</option>
                    <option value="hybrid" <?= ($bahan_bakar == 'hybrid') ? 'selected' : '' ?>>Hybrid</option>
                </select>
                <?php if (isset($errors['bahan_bakar'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['bahan_bakar'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="kategori_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori <span class="text-red-600">*</span></label>
                <select id="kategori_id" name="kategori_id" class="w-full px-3 py-2 border <?= isset($errors['kategori_id']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <option value="">-- Pilih Kategori --</option>
                    <?php if (!empty($kategoriList)): ?>
                        <?php foreach ($kategoriList as $kategori): ?>
                        <option value="<?= $kategori['id'] ?>" <?= ($kategori_id == $kategori['id']) ? 'selected' : '' ?>><?= htmlspecialchars($kategori['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <option value="" disabled>Tidak ada kategori</option>
                    <?php endif; ?>
                </select>
                <?php if (isset($errors['kategori_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['kategori_id'] ?></p>
                <?php elseif (empty($kategoriList)): ?>
                <p class="mt-1 text-sm text-amber-600">Belum ada kategori. <a href="<?= ADMIN_URL ?>kategori/tambah.php" class="text-primary-600 hover:underline">Tambah kategori</a> terlebih dahulu.</p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="harga_sewa_per_hari" class="block text-sm font-medium text-gray-700 mb-1">Harga Sewa Per Hari <span class="text-red-600">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">Rp</span>
                    </div>
                    <input type="text" id="harga_sewa_per_hari" name="harga_sewa_per_hari" value="<?= number_format($harga_sewa_per_hari, 0, ',', '.') ?>" class="w-full pl-10 pr-3 py-2 border <?= isset($errors['harga_sewa_per_hari']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="100.000">
                </div>
                <?php if (isset($errors['harga_sewa_per_hari'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['harga_sewa_per_hari'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-600">*</span></label>
                <select id="status" name="status" class="w-full px-3 py-2 border <?= isset($errors['status']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <option value="tersedia" <?= ($status == 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
                    <option value="disewa" <?= ($status == 'disewa') ? 'selected' : '' ?>>Sedang Disewa</option>
                    <option value="pemeliharaan" <?= ($status == 'pemeliharaan') ? 'selected' : '' ?>>Pemeliharaan</option>
                </select>
                <?php if (isset($errors['status'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['status'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-6">
            <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" class="w-full px-3 py-2 border <?= isset($errors['deskripsi']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Deskripsi kendaraan..."><?= htmlspecialchars($deskripsi) ?></textarea>
            <?php if (isset($errors['deskripsi'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= $errors['deskripsi'] ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Foto Mobil</label>
            
            <?php if (!empty($foto_mobil_lama)): ?>
            <div class="mb-3">
                <p class="text-sm text-gray-600 mb-2">Foto saat ini:</p>
                <div class="w-40 h-40 rounded-lg overflow-hidden border border-gray-200">
                    <img src="<?= BASE_URL ?>assets/uploads/mobil/<?= $foto_mobil_lama ?>" alt="Foto Mobil" class="w-full h-full object-cover">
                </div>
            </div>
            <?php endif; ?>
            
            <div>
                <label for="foto_mobil" class="block text-sm font-medium text-gray-700 mb-1">Upload Foto Baru (Opsional)</label>
                <input type="file" id="foto_mobil" name="foto_mobil" accept="image/jpeg,image/jpg,image/png,image/webp" class="w-full px-3 py-2 border <?= isset($errors['foto_mobil']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, atau WEBP. Maksimal 2MB.</p>
                <?php if (isset($errors['foto_mobil'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['foto_mobil'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <a href="<?= ADMIN_URL ?>mobil/index.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Batal
            </a>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-save mr-2"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format harga dengan titik sebagai pemisah ribuan
    const hargaInput = document.getElementById('harga_sewa_per_hari');
    
    hargaInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value === '') return;
        
        value = parseInt(value, 10);
        e.target.value = value.toLocaleString('id-ID').replace(/,/g, '.');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 