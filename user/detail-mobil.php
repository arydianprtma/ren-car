<?php
require_once 'includes/header.php';

// Periksa apakah parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect ke halaman mobil jika tidak ada ID
    header("Location: " . USER_URL . "mobil.php");
    exit;
}

// Variabel untuk JavaScript redirect
$js_redirect = "";
$redirect_url = "";

// Ambil ID mobil
$id_mobil = $_GET['id'];

// Inisialisasi variabel untuk form pemesanan
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';
$errors = [];
$success = false;

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil detail mobil dari database
try {
    $stmt = $conn->prepare("SELECT m.*, k.nama_kategori 
                            FROM mobil m 
                            LEFT JOIN kategori_mobil k ON m.kategori_id = k.id 
                            WHERE m.id = ? AND m.status = 'tersedia'");
    $stmt->execute([$id_mobil]);
    $mobil = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika mobil tidak ditemukan atau tidak tersedia, redirect ke halaman mobil
    if (!$mobil) {
        $_SESSION['flash_message'] = "Mobil tidak ditemukan atau tidak tersedia";
        $_SESSION['flash_type'] = "red";
        header("Location: " . USER_URL . "mobil.php");
        exit;
    }

    // Ambil fitur mobil dari data JSON yang tersimpan di database
    $fiturMobil = [];
    if (!empty($mobil['fitur'])) {
        $fiturJson = json_decode($mobil['fitur'], true);
        
        // Definisi nama fitur berdasarkan key
        $fiturMapping = [
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
        
        // Buat array fitur dengan label yang benar
        foreach ($fiturJson as $fiturKey) {
            $fiturMobil[$fiturMapping[$fiturKey] ?? $fiturKey] = true;
        }
    }

    // Ambil review untuk mobil ini (future feature)
    $reviews = [];

} catch (PDOException $e) {
    // Handle error
    $_SESSION['flash_message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "mobil.php");
    exit;
}

// Proses form pemesanan jika ada POST request dan user sudah login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    // Ambil data form
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    $user_id = $_SESSION['user_id'];
    $total_hari = 0;
    
    // Validasi input
    if (empty($tanggal_mulai)) {
        $errors['tanggal_mulai'] = 'Tanggal mulai sewa harus diisi';
    } elseif (strtotime($tanggal_mulai) < strtotime(date('Y-m-d'))) {
        $errors['tanggal_mulai'] = 'Tanggal mulai sewa tidak boleh kurang dari hari ini';
    }
    
    if (empty($tanggal_selesai)) {
        $errors['tanggal_selesai'] = 'Tanggal selesai sewa harus diisi';
    } elseif (strtotime($tanggal_selesai) <= strtotime($tanggal_mulai)) {
        $errors['tanggal_selesai'] = 'Tanggal selesai sewa harus lebih besar dari tanggal mulai';
    } else {
        // Hitung total hari sewa
        $total_hari = ceil((strtotime($tanggal_selesai) - strtotime($tanggal_mulai)) / (60 * 60 * 24));
    }
    
    // Periksa ketersediaan mobil pada tanggal yang dipilih
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM pemesanan 
                                WHERE mobil_id = ? 
                                AND status_pemesanan NOT IN ('dibatalkan', 'selesai') 
                                AND ((tanggal_mulai BETWEEN ? AND ?) 
                                OR (tanggal_selesai BETWEEN ? AND ?) 
                                OR (tanggal_mulai <= ? AND tanggal_selesai >= ?))");
        $stmt->execute([
            $id_mobil, 
            $tanggal_mulai, $tanggal_selesai, 
            $tanggal_mulai, $tanggal_selesai, 
            $tanggal_mulai, $tanggal_selesai
        ]);
        
        if ($stmt->rowCount() > 0) {
            $errors['tanggal'] = 'Mobil tidak tersedia pada tanggal yang dipilih. Silakan pilih tanggal lain.';
        }
    }
    
    // Jika tidak ada error, simpan pemesanan
    if (empty($errors)) {
        try {
            // Mulai transaksi
            $conn->beginTransaction();
            
            // Hitung total biaya
            $total_harga = $mobil['harga_sewa_per_hari'] * $total_hari;
            
            // Generate kode pemesanan
            $kode_pemesanan = 'BKG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            
            // Simpan data pemesanan
            $stmt = $conn->prepare("INSERT INTO pemesanan (kode_pemesanan, user_id, mobil_id, tanggal_mulai, tanggal_selesai, 
                                   total_harga, status_pemesanan, catatan, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, 'menunggu', ?, NOW())");
            $stmt->execute([
                $kode_pemesanan, $user_id, $id_mobil, $tanggal_mulai, $tanggal_selesai, $total_harga, $catatan
            ]);
            
            // Dapatkan ID pemesanan yang baru saja dibuat
            $pemesanan_id = $conn->lastInsertId();
            
            // Update status mobil menjadi 'disewa'
            $stmt = $conn->prepare("UPDATE mobil SET status = 'disewa' WHERE id = ?");
            $stmt->execute([$id_mobil]);
            
            // Kirim notifikasi pengingat pembayaran
            require_once '../classes/Notification.php';
            $notification = new Notification($conn);
            $notification->sendPaymentReminder($pemesanan_id);
            
            // Commit transaksi
            $conn->commit();
            
            // Set success message
            $_SESSION['flash_message'] = "Pemesanan berhasil dibuat dengan kode: " . $kode_pemesanan;
            $_SESSION['flash_type'] = "green";
            
            // Set redirect URL untuk JavaScript
            $redirect_url = USER_URL . "pemesanan_detail.php?kode=" . $kode_pemesanan;
            $js_redirect = 'window.location.href = "' . $redirect_url . '";';
            
        } catch (PDOException $e) {
            // Rollback transaksi jika terjadi error
            $conn->rollback();
            $errors['db'] = 'Gagal menyimpan pemesanan: ' . $e->getMessage();
        }
    }
}
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-3">
    <div class="container mx-auto px-6">
        <div class="flex text-sm">
            <a href="<?= USER_URL ?>" class="text-blue-600 hover:text-blue-800">Beranda</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="<?= USER_URL ?>mobil.php" class="text-blue-600 hover:text-blue-800">Mobil</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-600"><?= $mobil['merk'] ?> <?= $mobil['model'] ?></span>
        </div>
    </div>
</div>

<!-- Detail Mobil Section -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Gambar Mobil -->
            <div>
                <div class="bg-gray-100 rounded-xl overflow-hidden shadow-lg h-80 mb-6">
                    <?php if (!empty($mobil['foto_mobil'])): ?>
                        <img src="<?= ASSETS_URL ?>uploads/mobil/<?= $mobil['foto_mobil'] ?>" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-gray-200">
                            <i class="fas fa-car-side text-5xl text-gray-400"></i>
                            <p class="ml-2 text-gray-500">Tidak ada foto</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Fitur Mobil -->
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-list-check mr-2 text-blue-600"></i> Fitur Mobil
                    </h3>
                    <?php if (empty($fiturMobil)): ?>
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i> Informasi fitur mobil tidak tersedia
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <?php foreach ($fiturMobil as $fitur => $tersedia): ?>
                                <div class="flex items-center <?= $tersedia ? 'text-gray-800' : 'text-gray-400' ?>">
                                    <?php if ($tersedia): ?>
                                        <i class="fas fa-check text-green-500 mr-2"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times text-red-500 mr-2"></i>
                                    <?php endif; ?>
                                    <?= $fitur ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Detail dan Form Pemesanan -->
            <div>
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200 mb-6">
                    <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1 rounded-full mb-2">
                        <?= $mobil['nama_kategori'] ?? 'Uncategorized' ?>
                    </span>
                    
                    <h1 class="text-3xl font-bold text-gray-800 mb-2"><?= $mobil['merk'] ?> <?= $mobil['model'] ?></h1>
                    
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            <?= ucfirst($mobil['status']) ?>
                        </div>
                        <div class="mx-2 text-gray-400">|</div>
                        <div class="text-gray-600 text-sm">
                            Plat Nomor: <span class="font-semibold"><?= $mobil['nomor_plat'] ?></span>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-100 my-4 pt-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Tahun</p>
                                <p class="font-semibold"><?= $mobil['tahun_produksi'] ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Transmisi</p>
                                <p class="font-semibold"><?= ucfirst($mobil['transmisi']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Kapasitas</p>
                                <p class="font-semibold"><?= $mobil['kapasitas'] ?> Orang</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Bahan Bakar</p>
                                <p class="font-semibold"><?= ucfirst($mobil['bahan_bakar']) ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($mobil['deskripsi'])): ?>
                            <div class="border-t border-gray-100 my-4 pt-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Deskripsi</h3>
                                <p class="text-gray-600"><?= nl2br(htmlspecialchars($mobil['deskripsi'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="bg-blue-50 p-4 rounded-lg text-center my-4">
                            <p class="text-gray-700 mb-1">Harga Sewa</p>
                            <p class="text-2xl font-bold text-blue-600">
                                Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?> <span class="text-sm font-normal text-gray-500">/ hari</span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Form Pemesanan -->
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i> Form Pemesanan
                    </h3>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($errors['db'])): ?>
                            <div class="mb-4 bg-red-100 text-red-700 p-3 rounded-lg">
                                <?= $errors['db'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai <span class="text-red-600">*</span></label>
                                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?= $tanggal_mulai ?>" min="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border <?= isset($errors['tanggal_mulai']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                    <?php if (isset($errors['tanggal_mulai'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['tanggal_mulai'] ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai <span class="text-red-600">*</span></label>
                                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="<?= $tanggal_selesai ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="w-full px-3 py-2 border <?= isset($errors['tanggal_selesai']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                    <?php if (isset($errors['tanggal_selesai'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['tanggal_selesai'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (isset($errors['tanggal'])): ?>
                                <div class="mb-4 bg-red-100 text-red-700 p-3 rounded-lg">
                                    <?= $errors['tanggal'] ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-4">
                                <label for="catatan" class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                                <textarea id="catatan" name="catatan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Tambahkan catatan atau permintaan khusus jika diperlukan"><?= isset($catatan) ? htmlspecialchars($catatan) : '' ?></textarea>
                            </div>
                            
                            <div id="summary" class="bg-gray-50 p-4 rounded-lg mb-6 hidden">
                                <h4 class="font-medium text-gray-800 mb-2">Ringkasan Pemesanan</h4>
                                <div class="flex justify-between mb-2">
                                    <span>Harga Sewa per Hari:</span>
                                    <span>Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?></span>
                                </div>
                                <div class="flex justify-between mb-2">
                                    <span>Durasi Sewa:</span>
                                    <span id="durasi">- hari</span>
                                </div>
                                <div class="flex justify-between font-bold text-blue-600 pt-2 border-t border-gray-200">
                                    <span>Total Biaya:</span>
                                    <span id="total_harga">-</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-all flex items-center">
                                    <i class="fas fa-shopping-cart mr-2"></i> Pesan Sekarang
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                            <p class="text-yellow-700 mb-4">Anda harus login terlebih dahulu untuk melakukan pemesanan</p>
                            <a href="<?= USER_URL ?>login.php?redirect=<?= urlencode('detail-mobil.php?id=' . $id_mobil) ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-all inline-block">
                                <i class="fas fa-sign-in-alt mr-2"></i> Login Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mobil Terkait Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Mobil Terkait</h2>
        
        <div id="related-cars" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Skeleton loader (akan diganti dengan mobil terkait) -->
            <?php for($i = 0; $i < 3; $i++): ?>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden skeleton-shimmer">
                    <div class="h-48 bg-gray-200"></div>
                    <div class="p-4">
                        <div class="h-6 bg-gray-200 rounded mb-2 w-3/4"></div>
                        <div class="h-4 bg-gray-200 rounded mb-2 w-1/2"></div>
                        <div class="h-8 bg-gray-200 rounded mt-4"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ambil elemen tanggal
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');
    const summary = document.getElementById('summary');
    const durasi = document.getElementById('durasi');
    const totalBiaya = document.getElementById('total_harga');
    const hargaPerHari = <?= $mobil['harga_sewa_per_hari'] ?>;
    
    // Function untuk menghitung dan menampilkan ringkasan
    function hitungRingkasan() {
        if (tanggalMulai.value && tanggalSelesai.value) {
            const tglMulai = new Date(tanggalMulai.value);
            const tglSelesai = new Date(tanggalSelesai.value);
            
            // Hitung selisih hari
            const selisihHari = Math.ceil((tglSelesai - tglMulai) / (1000 * 60 * 60 * 24));
            
            if (selisihHari > 0) {
                // Tampilkan ringkasan
                durasi.textContent = selisihHari + ' hari';
                const total = selisihHari * hargaPerHari;
                totalBiaya.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
                summary.classList.remove('hidden');
            } else {
                summary.classList.add('hidden');
            }
        } else {
            summary.classList.add('hidden');
        }
    }
    
    // Event listener untuk perubahan tanggal
    tanggalMulai.addEventListener('change', function() {
        // Update minimum tanggal selesai
        if (tanggalMulai.value) {
            const nextDay = new Date(tanggalMulai.value);
            nextDay.setDate(nextDay.getDate() + 1);
            tanggalSelesai.min = nextDay.toISOString().split('T')[0];
            
            // Reset tanggal selesai jika sebelum minimum
            if (tanggalSelesai.value && new Date(tanggalSelesai.value) <= new Date(tanggalMulai.value)) {
                tanggalSelesai.value = nextDay.toISOString().split('T')[0];
            }
        }
        
        hitungRingkasan();
    });
    
    tanggalSelesai.addEventListener('change', hitungRingkasan);
    
    // Jika sudah ada nilai, hitung ringkasan
    if (tanggalMulai.value && tanggalSelesai.value) {
        hitungRingkasan();
    }
    
    // Load mobil terkait setelah 1.5 detik (simulasi loading)
    setTimeout(function() {
        fetch('ajax/related_cars.php?id=<?= $id_mobil ?>&kategori=<?= $mobil['kategori_id'] ?>')
            .then(response => response.text())
            .then(html => {
                document.getElementById('related-cars').innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading related cars:', error);
            });
    }, 1500);
});
</script>

<!-- JavaScript redirect -->
<?php if (!empty($js_redirect)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?= $js_redirect ?>
    });
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 