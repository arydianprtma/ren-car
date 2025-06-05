<?php
// Inisialisasi koneksi database dan session
require_once '../config/config.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Anda harus login terlebih dahulu";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "login.php");
    exit;
}

// Periksa apakah parameter kode ada
if (!isset($_GET['kode']) || empty($_GET['kode'])) {
    $_SESSION['flash_message'] = "Kode pemesanan tidak valid";
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "pemesanan.php");
    exit;
}

$kode_pemesanan = $_GET['kode'];
$user_id = $_SESSION['user_id'];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil detail pemesanan
try {
    $stmt = $conn->prepare("SELECT p.*, m.merk, m.model, m.nomor_plat, m.foto_mobil, 
                           m.harga_sewa_per_hari, u.nama as nama_user, u.email, u.no_telp as telepon
                           FROM pemesanan p
                           JOIN mobil m ON p.mobil_id = m.id
                           JOIN users u ON p.user_id = u.id
                           WHERE p.kode_pemesanan = ? AND p.user_id = ?");
    $stmt->execute([$kode_pemesanan, $user_id]);
    $pemesanan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika pemesanan tidak ditemukan, redirect ke halaman pemesanan
    if (!$pemesanan) {
        $_SESSION['flash_message'] = "Pemesanan tidak ditemukan";
        $_SESSION['flash_type'] = "red";
        header("Location: " . USER_URL . "pemesanan.php");
        exit;
    }

    // Hitung durasi sewa
    $tanggal_mulai = new DateTime($pemesanan['tanggal_mulai']);
    $tanggal_selesai = new DateTime($pemesanan['tanggal_selesai']);
    $durasi = $tanggal_mulai->diff($tanggal_selesai)->days;

} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    header("Location: " . USER_URL . "pemesanan.php");
    exit;
}

// Proses pembayaran jika ada POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metode_pembayaran'])) {
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $bukti_pembayaran = '';
    $errors = [];
    
    // Periksa jika status masih menunggu pembayaran
    if ($pemesanan['status_pemesanan'] !== 'menunggu') {
        $errors['status'] = 'Pemesanan ini sudah dibayar atau diproses';
    }
    
    // Validasi bukti pembayaran untuk transfer bank
    if ($metode_pembayaran === 'transfer_bank' && empty($_FILES['bukti_pembayaran']['name'])) {
        $errors['bukti_pembayaran'] = 'Bukti pembayaran harus diunggah untuk metode transfer bank';
    } elseif ($metode_pembayaran === 'transfer_bank' && !empty($_FILES['bukti_pembayaran']['name'])) {
        // Validasi file upload
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['bukti_pembayaran']['type'], $allowed_types)) {
            $errors['bukti_pembayaran'] = 'Format file tidak didukung. Gunakan JPG, PNG, WEBP, atau PDF';
        } elseif ($_FILES['bukti_pembayaran']['size'] > $max_size) {
            $errors['bukti_pembayaran'] = 'Ukuran file terlalu besar (maksimal 2MB)';
        } else {
            // Generate nama file unik
            $extension = pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION);
            $bukti_pembayaran = 'payment_' . $kode_pemesanan . '_' . time() . '.' . $extension;
        }
    }
    
    // Jika tidak ada error, lakukan proses pembayaran
    if (empty($errors)) {
        try {
            // Mulai transaksi
            $conn->beginTransaction();
            
            // Upload bukti pembayaran jika metode transfer bank
            if ($metode_pembayaran === 'transfer_bank' && !empty($bukti_pembayaran)) {
                $upload_dir = '../assets/uploads/pembayaran/';
                
                // Buat direktori jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Upload file
                move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $upload_dir . $bukti_pembayaran);
            }
            
            // Update status pemesanan
            $status_baru = 'dikonfirmasi';  // Ubah status menjadi 'dikonfirmasi' saat pembayaran berhasil diproses (sesuai ENUM yang benar)
            
            $stmt = $conn->prepare("UPDATE pemesanan SET 
                                   status_pemesanan = :status, 
                                   metode_pembayaran = :metode, 
                                   bukti_pembayaran = :bukti,
                                   updated_at = NOW()
                                   WHERE kode_pemesanan = :kode");
            
            $stmt->bindParam(':status', $status_baru, PDO::PARAM_STR);
            $stmt->bindParam(':metode', $metode_pembayaran, PDO::PARAM_STR);
            $stmt->bindParam(':bukti', $bukti_pembayaran, PDO::PARAM_STR);
            $stmt->bindParam(':kode', $kode_pemesanan, PDO::PARAM_STR);
            $stmt->execute();
            
            // Verifikasi bahwa update berhasil
            $check_stmt = $conn->prepare("SELECT id, status_pemesanan, metode_pembayaran FROM pemesanan WHERE kode_pemesanan = :kode");
            $check_stmt->bindParam(':kode', $kode_pemesanan, PDO::PARAM_STR);
            $check_stmt->execute();
            $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug
            error_log("Status pemesanan: " . $check_result['status_pemesanan'] . ", Metode pembayaran: " . $check_result['metode_pembayaran']);
            
            // Kirim notifikasi pembayaran berhasil
            require_once '../classes/Notification.php';
            $notification = new Notification($conn);
            $notification->sendPaymentConfirmation($check_result['id']);
            
            // Commit transaksi
            $conn->commit();
            
            // Ambil data terbaru setelah update
            $refresh_stmt = $conn->prepare("SELECT status_pemesanan, metode_pembayaran FROM pemesanan WHERE kode_pemesanan = :kode");
            $refresh_stmt->bindParam(':kode', $kode_pemesanan, PDO::PARAM_STR);
            $refresh_stmt->execute();
            $updated_data = $refresh_stmt->fetch(PDO::FETCH_ASSOC);
            
            $metode_text = ($metode_pembayaran === 'transfer_bank') ? 'Transfer Bank' : 'Bayar di Tempat';
            
            // Set flash message
            $_SESSION['flash_message'] = "Pembayaran berhasil diproses dengan metode " . $metode_text . "! Status pemesanan: Dikonfirmasi";
            $_SESSION['flash_type'] = "green";
            
            // Redirect ke halaman yang sama untuk refresh data
            header("Location: " . USER_URL . "pemesanan_detail.php?kode=" . $kode_pemesanan);
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaksi jika terjadi error
            $conn->rollback();
            $errors['db'] = 'Gagal memproses pembayaran: ' . $e->getMessage();
            
            // Hapus file yang sudah diupload jika ada
            if (!empty($bukti_pembayaran) && file_exists('../assets/uploads/pembayaran/' . $bukti_pembayaran)) {
                unlink('../assets/uploads/pembayaran/' . $bukti_pembayaran);
            }
        }
    }
}

// Fungsi untuk mendapatkan label status
function getStatusLabel($status) {
    // Pastikan status tidak null/empty
    if (empty($status)) {
        return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">Menunggu</span>';
    }
    
    switch ($status) {
        case 'menunggu':
            return '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">Menunggu Pembayaran</span>';
        case 'dikonfirmasi':
            return '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">Dikonfirmasi</span>';
        case 'berjalan':
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Berjalan</span>';
        case 'selesai':
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Selesai</span>';
        case 'dibatalkan':
            return '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">Dibatalkan</span>';
        default:
            return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
    }
}

// Setelah semua proses selesai, baru include header.php
require_once 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-3">
    <div class="container mx-auto px-6">
        <div class="flex text-sm">
            <a href="<?= USER_URL ?>" class="text-blue-600 hover:text-blue-800">Beranda</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="<?= USER_URL ?>pemesanan.php" class="text-blue-600 hover:text-blue-800">Pemesanan Saya</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-600">Detail Pemesanan</span>
        </div>
    </div>
</div>

<!-- Detail Pemesanan Section -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-6">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Detail Pemesanan</h1>
                    <p class="text-gray-600">Kode Pemesanan: <span class="font-semibold"><?= $pemesanan['kode_pemesanan'] ?></span></p>
                </div>
                                <div class="mt-4 md:mt-0">                    <?= getStatusLabel($pemesanan['status_pemesanan']) ?>                </div>
            </div>
            
            <?php if (isset($errors['db'])): ?>
            <div class="mb-6 bg-red-100 text-red-700 p-4 rounded-lg">
                <?= $errors['db'] ?>
            </div>
            <?php endif; ?>
            
            <!-- Kartu Detail Mobil -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-3">
                    <!-- Gambar Mobil -->
                    <div class="col-span-1 md:col-span-1 h-48 md:h-60 bg-gray-100 rounded-lg overflow-hidden">
                        <?php if (!empty($pemesanan['foto_mobil'])): ?>
                            <img src="<?= ASSETS_URL ?>uploads/mobil/<?= $pemesanan['foto_mobil'] ?>" alt="<?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center">
                                <i class="fas fa-car-side text-5xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500">Foto tidak tersedia</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Detail Pemesanan -->
                    <div class="p-6 md:col-span-2">
                        <h2 class="text-xl font-bold text-gray-800 mb-3"><?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?></h2>
                        <p class="text-gray-600 mb-1">Nomor Plat: <span class="font-medium"><?= $pemesanan['nomor_plat'] ?></span></p>
                        
                        <!-- Tampilkan fitur mobil jika tersedia -->
                        <?php
                        if (!empty($pemesanan['fitur'])) {
                            $fiturJson = json_decode($pemesanan['fitur'], true);
                            if (!empty($fiturJson)) {
                                echo '<div class="mt-3 mb-4 flex flex-wrap gap-2">';
                                
                                $fiturMapping = [
                                    'ac' => ['label' => 'AC', 'icon' => 'snowflake'],
                                    'power_steering' => ['label' => 'Power Steering', 'icon' => 'steering-wheel'],
                                    'power_window' => ['label' => 'Power Window', 'icon' => 'window-maximize'],
                                    'central_lock' => ['label' => 'Central Lock', 'icon' => 'lock'],
                                    'audio_system' => ['label' => 'Audio System', 'icon' => 'music'],
                                    'airbag' => ['label' => 'Airbag', 'icon' => 'car-burst'],
                                    'seatbelt' => ['label' => 'Seat Belt', 'icon' => 'user-shield'],
                                    'pewangi' => ['label' => 'Pewangi Mobil', 'icon' => 'spray-can-sparkles'],
                                    'bluetooth' => ['label' => 'Bluetooth', 'icon' => 'bluetooth'],
                                    'cruise_control' => ['label' => 'Cruise Control', 'icon' => 'tachometer-alt'],
                                    'parking_sensor' => ['label' => 'Parking Sensor', 'icon' => 'parking'],
                                    'backup_camera' => ['label' => 'Backup Camera', 'icon' => 'camera'],
                                    'child_lock' => ['label' => 'Child Lock', 'icon' => 'child'],
                                    'fog_lamp' => ['label' => 'Fog Lamp', 'icon' => 'lightbulb'],
                                    'kursi_bayi' => ['label' => 'Kursi Bayi', 'icon' => 'baby']
                                ];
                                
                                foreach ($fiturJson as $fiturKey) {
                                    if (isset($fiturMapping[$fiturKey])) {
                                        echo '<span class="inline-flex items-center bg-blue-50 px-2 py-1 rounded text-xs font-medium text-gray-700 border border-blue-100">';
                                        echo '<i class="fas fa-' . $fiturMapping[$fiturKey]['icon'] . ' text-blue-500 mr-1"></i> ';
                                        echo $fiturMapping[$fiturKey]['label'];
                                        echo '</span>';
                                    }
                                }
                                
                                echo '</div>';
                            }
                        }
                        ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-2">Informasi Penyewa</h3>
                                <p class="text-gray-600 text-sm mb-1">Nama: <?= $pemesanan['nama_user'] ?></p>
                                <p class="text-gray-600 text-sm mb-1">Email: <?= $pemesanan['email'] ?></p>
                                <p class="text-gray-600 text-sm">Telepon: <?= $pemesanan['telepon'] ?></p>
                            </div>
                            
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-2">Detail Sewa</h3>
                                <p class="text-gray-600 text-sm mb-1">Tanggal Mulai: <?= date('d F Y', strtotime($pemesanan['tanggal_mulai'])) ?></p>
                                <p class="text-gray-600 text-sm mb-1">Tanggal Selesai: <?= date('d F Y', strtotime($pemesanan['tanggal_selesai'])) ?></p>
                                <p class="text-gray-600 text-sm">Durasi: <?= $durasi ?> hari</p>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-100 mt-4 pt-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Rincian Biaya</h3>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600 text-sm">Harga Sewa per Hari:</span>
                                <span class="text-gray-800">Rp <?= number_format($pemesanan['harga_sewa_per_hari'], 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600 text-sm">Durasi Sewa:</span>
                                <span class="text-gray-800"><?= $durasi ?> hari</span>
                            </div>
                                                                        <div class="flex justify-between font-bold text-lg border-t border-gray-100 pt-2 mt-2">                                <span class="text-gray-800">Total Biaya:</span>                                <span class="text-blue-600">Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?></span>                            </div>
                        </div>
                        
                        <?php if (!empty($pemesanan['catatan'])): ?>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Catatan</h3>
                            <p class="text-gray-600 text-sm"><?= nl2br(htmlspecialchars($pemesanan['catatan'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Pembayaran (Hanya ditampilkan jika status 'menunggu pembayaran') -->
            <?php if ($pemesanan['status_pemesanan'] === 'menunggu'): ?>
                <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-700 to-blue-500 text-white px-6 py-4">
                        <h3 class="text-lg font-semibold"><i class="fas fa-credit-card mr-2"></i> Pembayaran</h3>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-4">
                            <p class="font-medium text-gray-700 mb-1">Total yang harus dibayar:</p>
                            <p class="text-2xl font-bold text-blue-600">Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?></p>
                        </div>

                        <div class="mb-4">
                            <p class="font-medium text-gray-700 mb-2">Pilih metode pembayaran:</p>
                            
                            <div class="space-y-4">
                                <!-- Midtrans Payment Gateway -->
                                <div class="p-4 border border-blue-200 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors cursor-pointer">
                                    <a href="<?= BASE_URL ?>payments/midtrans/process.php?kode=<?= $pemesanan['kode_pemesanan'] ?>" class="block">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mr-3 shadow-sm">
                                                    <i class="fas fa-credit-card text-blue-600"></i>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-800">Pembayaran Online</h4>
                                                    <p class="text-sm text-gray-600">Bayar dengan kartu kredit, virtual account, e-wallet, dll.</p>
                                                </div>
                                            </div>
                                            <i class="fas fa-chevron-right text-gray-400"></i>
                                        </div>
                                    </a>
                                </div>
                            
                                <!-- Metode Pembayaran Transfer Bank -->
                                <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                                    <div class="flex items-center justify-between" data-toggle="collapse" data-target="#transferBankForm">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                <i class="fas fa-university text-blue-600"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Transfer Bank Manual</h4>
                                                <p class="text-sm text-gray-600">Lakukan transfer manual dan unggah bukti pembayaran</p>
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                    </div>
                                    
                                    <div id="transferBankForm" class="mt-4 hidden">
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                            <p class="text-sm text-yellow-800">
                                                <i class="fas fa-info-circle mr-2"></i> Transfer ke rekening bank kami dan unggah bukti pembayaran di bawah ini.
                                            </p>
                                            <div class="mt-2">
                                                <p class="text-sm"><span class="font-medium">Bank:</span> BCA</p>
                                                <p class="text-sm"><span class="font-medium">No. Rekening:</span> 1234567890</p>
                                                <p class="text-sm"><span class="font-medium">Atas Nama:</span> PT. Rental Mobil</p>
                                                <p class="text-sm"><span class="font-medium">Jumlah:</span> Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?></p>
                                            </div>
                                        </div>
                                        
                                        <form action="" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="metode_pembayaran" value="transfer_bank">
                                            
                                            <div class="mb-4">
                                                <label for="bukti_pembayaran" class="block text-sm font-medium text-gray-700 mb-1">Bukti Pembayaran</label>
                                                <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" accept="image/jpeg,image/png,image/webp,application/pdf" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                                <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, WEBP, PDF. Maks. 2MB</p>
                                                <?php if (isset($errors['bukti_pembayaran'])): ?>
                                                    <p class="text-red-500 text-sm mt-1"><?= $errors['bukti_pembayaran'] ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all">
                                                <i class="fas fa-upload mr-2"></i> Upload Bukti Pembayaran
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Tombol Kembali dan Bantuan -->
            <div class="flex flex-col md:flex-row justify-between items-center mt-6">
                <a href="<?= USER_URL ?>pemesanan.php" class="mb-4 md:mb-0 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg transition-all flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Pemesanan
                </a>
                <a href="<?= USER_URL ?>kontak.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition-all flex items-center">
                    <i class="fas fa-headset mr-2"></i> Butuh Bantuan?
                </a>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle tampilan detail transfer bank
    const radioButtons = document.querySelectorAll('input[name="metode_pembayaran"]');
    const transferDetails = document.getElementById('transfer-details');
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'transfer_bank') {
                transferDetails.classList.remove('hidden');
            } else {
                transferDetails.classList.add('hidden');
            }
        });
    });

    // Script untuk toggle form transfer bank
    const toggleElements = document.querySelectorAll('[data-toggle="collapse"]');
    
    toggleElements.forEach(function(element) {
        element.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                if (targetElement.classList.contains('hidden')) {
                    targetElement.classList.remove('hidden');
                    this.querySelector('.fas.fa-chevron-down').classList.add('transform', 'rotate-180');
                } else {
                    targetElement.classList.add('hidden');
                    this.querySelector('.fas.fa-chevron-down').classList.remove('transform', 'rotate-180');
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 