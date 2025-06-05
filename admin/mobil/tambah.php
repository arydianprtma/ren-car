<?php
/**
 * Tambah Mobil - Admin Panel
 */
require_once '../includes/auth_check.php';

// Inisialisasi variabel
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
$errors = [];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Definisi fitur-fitur mobil yang tersedia
$fiturMobil = [
    'ac' => 'AC',
    'power_steering' => 'Power Steering',
    'power_window' => 'Power Window',
    'central_lock' => 'Central Lock',
    'audio_system' => 'Audio System',
    'airbag' => 'Airbag',
    'seatbelt' => 'Seat Belt',
    'pewangi' => 'Pewangi Mobil',
    'bluetooth' => 'Bluetooth Connectivity',
    'cruise_control' => 'Cruise Control',
    'parking_sensor' => 'Parking Sensor',
    'backup_camera' => 'Backup Camera',
    'child_lock' => 'Child Lock',
    'fog_lamp' => 'Fog Lamp',
    'kursi_bayi' => 'Kursi Bayi'
];

// Default fitur yang dipilih (kosong untuk form tambah)
$selectedFitur = [];

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
    
    // Ambil data fitur mobil yang dipilih
    $selectedFitur = isset($_POST['fitur_mobil']) ? $_POST['fitur_mobil'] : [];
    
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
    } else {
        // Cek apakah nomor plat sudah ada
        $stmt = $conn->prepare("SELECT id FROM mobil WHERE nomor_plat = ?");
        $stmt->execute([$nomor_plat]);
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
    $foto_mobil = '';
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
        }
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            // Mulai transaksi
            $conn->beginTransaction();
            
            // Upload file jika ada
            if (!empty($foto_mobil)) {
                $upload_dir = '../../assets/uploads/mobil/';
                
                // Cek dan buat direktori jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Upload file
                move_uploaded_file($_FILES['foto_mobil']['tmp_name'], $upload_dir . $foto_mobil);
            }
            
            // Simpan data mobil
            $sql = "INSERT INTO mobil (merk, model, nomor_plat, tahun_produksi, warna, kapasitas, 
                    transmisi, bahan_bakar, kategori_id, harga_sewa_per_hari, status, deskripsi, foto_mobil, fitur, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            // Encode fitur sebagai JSON untuk disimpan di database
            $fiturJson = !empty($selectedFitur) ? json_encode($selectedFitur) : NULL;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $merk, $model, $nomor_plat, $tahun_produksi, $warna, $kapasitas,
                $transmisi, $bahan_bakar, $kategori_id, $harga_sewa_per_hari, $status, $deskripsi, $foto_mobil, $fiturJson
            ]);
            
            // Commit transaksi
            $conn->commit();
            
            $_SESSION['flash_message'] = 'Mobil berhasil ditambahkan!';
            $_SESSION['flash_type'] = 'green';
            
            // Redirect ke halaman daftar mobil
            header("Location: " . ADMIN_URL . "mobil/index.php");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            $conn->rollback();
            
            $errors['db'] = 'Gagal menyimpan data: ' . $e->getMessage();
            
            // Hapus file yang sudah diupload jika ada
            if (!empty($foto_mobil) && file_exists('../../assets/uploads/mobil/' . $foto_mobil)) {
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
            <i class="fas fa-plus-circle mr-3 text-primary-600"></i> Tambah Mobil
        </h1>
        <p class="text-sm text-gray-600">Tambahkan mobil baru ke dalam sistem</p>
    </div>
    <a href="<?= ADMIN_URL ?>mobil/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Kembali
    </a>
</div>

<!-- Form Tambah Mobil -->
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
                <label for="kategori_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori Mobil <span class="text-red-600">*</span></label>
                <select id="kategori_id" name="kategori_id" class="w-full px-3 py-2 border <?= isset($errors['kategori_id']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategoriList as $kategori): ?>
                    <option value="<?= $kategori['id'] ?>" <?= ($kategori_id == $kategori['id']) ? 'selected' : '' ?>><?= $kategori['nama_kategori'] ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['kategori_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= $errors['kategori_id'] ?></p>
                <?php endif; ?>
                <?php if (empty($kategoriList)): ?>
                <p class="mt-1 text-sm text-amber-600">Belum ada kategori. <a href="<?= ADMIN_URL ?>kategori/tambah.php" class="text-primary-600 hover:underline">Tambah kategori</a> terlebih dahulu.</p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="harga_sewa_per_hari" class="block text-sm font-medium text-gray-700 mb-1">Harga Sewa per Hari <span class="text-red-600">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">Rp</span>
                    </div>
                    <input type="text" id="harga_sewa_per_hari" name="harga_sewa_per_hari" value="<?= !empty($harga_sewa_per_hari) ? number_format($harga_sewa_per_hari, 0, ',', '.') : '' ?>" class="w-full pl-10 pr-3 py-2 border <?= isset($errors['harga_sewa_per_hari']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: 300.000">
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
            
            <div class="md:col-span-2">
                <label for="foto_mobil" class="block text-sm font-medium text-gray-700 mb-1">Foto Mobil</label>
                <div class="flex items-center space-x-4">
                    <div class="w-full">
                        <input type="file" id="foto_mobil" name="foto_mobil" class="hidden" accept="image/jpeg, image/png, image/webp">
                        <label for="foto_mobil" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors flex items-center justify-between cursor-pointer hover:bg-gray-50">
                            <span id="foto-label" class="text-gray-500">Pilih file foto (JPG, PNG, WEBP, max 2MB)</span>
                            <span class="bg-gray-200 px-3 py-1 rounded-md text-gray-700"><i class="fas fa-upload"></i></span>
                        </label>
                        <?php if (isset($errors['foto_mobil'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['foto_mobil'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div id="preview-container" class="hidden w-20 h-20 bg-gray-100 rounded-lg overflow-hidden relative">
                        <img id="preview-image" src="#" alt="Preview" class="w-full h-full object-cover">
                        <button type="button" id="remove-image" class="absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="md:col-span-2 mt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Fitur Mobil</h3>
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-4">
                    <p class="text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i> Centang fitur-fitur yang tersedia pada mobil ini. Fitur yang dipilih akan ditampilkan dalam detail mobil untuk penyewa.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <?php foreach ($fiturMobil as $key => $label): ?>
                    <div class="flex items-center">
                        <input type="checkbox" id="fitur_<?= $key ?>" name="fitur_mobil[]" value="<?= $key ?>" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500" <?= in_array($key, $selectedFitur) ? 'checked' : '' ?>>
                        <label for="fitur_<?= $key ?>" class="ml-2 text-sm text-gray-700"><?= $label ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="md:col-span-2 mt-4">
                <label for="fitur_custom" class="block text-sm font-medium text-gray-700 mb-1">Fitur Tambahan (opsional)</label>
                <input type="text" id="fitur_custom" name="fitur_custom" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Contoh: GPS, Child Seat, dll (pisahkan dengan koma)">
                <p class="text-xs text-gray-500 mt-1">Masukkan fitur tambahan yang tidak ada dalam daftar di atas. Pisahkan dengan koma untuk beberapa fitur.</p>
            </div>
            
            <div class="md:col-span-2">
                <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Masukkan deskripsi mobil (opsional)"><?= htmlspecialchars($deskripsi) ?></textarea>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-end space-x-2">
                <button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition duration-200">
                    Reset
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition duration-200">
                    <i class="fas fa-save mr-2"></i> Simpan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Panduan -->
<div class="mt-6 bg-blue-50 rounded-lg p-4 border border-blue-100">
    <h3 class="text-lg font-semibold text-blue-800 mb-2">Panduan Penambahan Mobil</h3>
    <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
        <li>Pastikan nomor plat unik dan belum pernah terdaftar sebelumnya</li>
        <li>Foto mobil sebaiknya memiliki ukuran yang seragam (disarankan landscape dengan rasio 16:9)</li>
        <li>Format harga sewa akan otomatis terformat saat Anda mengetik</li>
        <li>Isi deskripsi dengan informasi tambahan yang relevan untuk penyewa</li>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto focus pada field merk
    document.getElementById('merk').focus();
    
    // Form fitur custom handling
    const fiturCustomInput = document.getElementById('fitur_custom');
    fiturCustomInput.addEventListener('change', function() {
        const customFiturs = this.value.split(',').map(item => item.trim()).filter(item => item !== '');
        if (customFiturs.length > 0) {
            console.log('Custom fiturs:', customFiturs);
            // Dapat diproses di sisi server
        }
    });
    
    // Format harga sewa
    const hargaInput = document.getElementById('harga_sewa_per_hari');
    
    hargaInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value === '') {
            e.target.value = '';
            return;
        }
        
        e.target.value = new Intl.NumberFormat('id-ID').format(value);
    });
    
    // Preview foto
    const fileInput = document.getElementById('foto_mobil');
    const fileLabel = document.getElementById('foto-label');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    const removeButton = document.getElementById('remove-image');
    
    fileInput.addEventListener('change', function() {
        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden');
                fileLabel.textContent = fileInput.files[0].name;
            }
            
            reader.readAsDataURL(fileInput.files[0]);
        }
    });
    
    removeButton.addEventListener('click', function() {
        fileInput.value = '';
        previewContainer.classList.add('hidden');
        fileLabel.textContent = 'Pilih file foto (JPG, PNG, WEBP, max 2MB)';
    });
    
    // Reset form
    const resetButton = document.querySelector('button[type=\"reset\"]');
    resetButton.addEventListener('click', function() {
        setTimeout(function() {
            previewContainer.classList.add('hidden');
            fileLabel.textContent = 'Pilih file foto (JPG, PNG, WEBP, max 2MB)';
        }, 100);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 