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

$user_id = $_SESSION['user_id'];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Proses permintaan pengembalian mobil
if (isset($_POST['action']) && $_POST['action'] === 'return' && isset($_POST['kode_pemesanan'])) {
    $kode_pemesanan = $_POST['kode_pemesanan'];
    $kondisi_mobil = isset($_POST['kondisi_mobil']) ? trim($_POST['kondisi_mobil']) : '';
    $catatan_tambahan = isset($_POST['catatan_tambahan']) ? trim($_POST['catatan_tambahan']) : '';
    
    try {
        // Mulai transaksi untuk memastikan integritas data
        $conn->beginTransaction();
        
        // Periksa apakah pesanan ada dan milik user yang sedang login
        $check_stmt = $conn->prepare("SELECT p.id, p.status_pemesanan, p.mobil_id, m.model, m.nomor_plat 
                                     FROM pemesanan p 
                                     JOIN mobil m ON p.mobil_id = m.id 
                                     WHERE p.kode_pemesanan = ? AND p.user_id = ?");
        $check_stmt->execute([$kode_pemesanan, $user_id]);
        $pemesanan = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pemesanan) {
            // Pesanan tidak ditemukan atau bukan milik user
            $_SESSION['flash_message'] = "Pesanan tidak ditemukan";
            $_SESSION['flash_type'] = "red";
            header("Location: " . USER_URL . "pesanan.php");
            exit;
        }
        
        // Periksa apakah status pesanan adalah 'berjalan'
        if ($pemesanan['status_pemesanan'] != 'berjalan') {
            $_SESSION['flash_message'] = "Pesanan tidak dapat dikembalikan karena status tidak valid";
            $_SESSION['flash_type'] = "red";
            header("Location: " . USER_URL . "pesanan.php");
            exit;
        }
        
        // Buat entri pengembalian
        $stmt = $conn->prepare("INSERT INTO pengembalian (pemesanan_id, tanggal_pengembalian, kondisi_mobil, catatan) 
                              VALUES (?, NOW(), ?, ?)");
        $stmt->execute([$pemesanan['id'], $kondisi_mobil, $catatan_tambahan]);
        
        // Update status pemesanan menjadi 'pending_return' (menunggu konfirmasi pengembalian)
        $update_stmt = $conn->prepare("UPDATE pemesanan SET status_pemesanan = 'pending_return', updated_at = NOW() WHERE kode_pemesanan = ?");
        $update_stmt->execute([$kode_pemesanan]);
        
        // Catatan: Status mobil tetap 'disewa' sampai admin mengkonfirmasi pengembalian
        // Tidak perlu mengubah status mobil di sini
        
        // Kirim notifikasi pengembalian ke admin
        require_once '../classes/Notification.php';
        $notif = new Notification($conn);
        
        $judul = "Permintaan Pengembalian Mobil";
        $pesan = "Pelanggan telah mengajukan pengembalian mobil " . $pemesanan['model'] . " (" . $pemesanan['nomor_plat'] . ") dengan kode pemesanan " . $kode_pemesanan . ".";
        
        if (!empty($kondisi_mobil)) {
            $pesan .= " Kondisi mobil: " . $kondisi_mobil;
        }
        
        if (!empty($catatan_tambahan)) {
            $pesan .= " Catatan tambahan: " . $catatan_tambahan;
        }
        
        $notif->createAdminNotification($judul, $pesan, 'pengembalian');
        
        // Commit transaksi
        $conn->commit();
        
        $_SESSION['flash_message'] = "Permintaan pengembalian mobil berhasil dikirim. Admin akan segera memproses pengembalian ini.";
        $_SESSION['flash_type'] = "green";
        header("Location: " . USER_URL . "pesanan.php");
        exit;
        
    } catch (PDOException $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollBack();
        
        $_SESSION['flash_message'] = "Gagal mengirimkan permintaan pengembalian: " . $e->getMessage();
        $_SESSION['flash_type'] = "red";
        header("Location: " . USER_URL . "pesanan.php");
        exit;
    }
}

// Proses pembatalan pesanan jika ada request pembatalan
if (isset($_POST['action']) && $_POST['action'] === 'cancel' && isset($_POST['kode_pemesanan'])) {
    $kode_pemesanan = $_POST['kode_pemesanan'];
    $alasan_pembatalan = isset($_POST['alasan_pembatalan']) ? trim($_POST['alasan_pembatalan']) : '';
    
    try {
        // Mulai transaksi untuk memastikan integritas data
        $conn->beginTransaction();
        
        // Periksa apakah pesanan ada dan milik user yang sedang login
        $check_stmt = $conn->prepare("SELECT p.id, p.status_pemesanan, p.mobil_id, m.model 
                                     FROM pemesanan p 
                                     JOIN mobil m ON p.mobil_id = m.id 
                                     WHERE p.kode_pemesanan = ? AND p.user_id = ?");
        $check_stmt->execute([$kode_pemesanan, $user_id]);
        $pemesanan = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pemesanan) {
            // Pesanan tidak ditemukan atau bukan milik user
            $_SESSION['flash_message'] = "Pesanan tidak ditemukan";
            $_SESSION['flash_type'] = "red";
            header("Location: " . USER_URL . "pesanan.php");
            exit;
        }
        
        // Periksa apakah status pesanan memungkinkan untuk dibatalkan
        if ($pemesanan['status_pemesanan'] != 'menunggu' && $pemesanan['status_pemesanan'] != 'dikonfirmasi') {
            $_SESSION['flash_message'] = "Pesanan tidak dapat dibatalkan karena status tidak valid";
            $_SESSION['flash_type'] = "red";
            header("Location: " . USER_URL . "pesanan.php");
            exit;
        }
        
        // Update status pesanan menjadi dibatalkan
        $update_stmt = $conn->prepare("UPDATE pemesanan SET status_pemesanan = 'dibatalkan', updated_at = NOW() WHERE kode_pemesanan = ?");
        $update_stmt->execute([$kode_pemesanan]);
        
        // Update status mobil menjadi tersedia kembali
        $update_mobil_stmt = $conn->prepare("UPDATE mobil SET status = 'tersedia' WHERE id = ?");
        $update_mobil_stmt->execute([$pemesanan['mobil_id']]);
        
        // Kirim notifikasi pembatalan ke admin
        require_once '../classes/Notification.php';
        $notif = new Notification($conn);
        $judul = "Pembatalan Pesanan #" . $kode_pemesanan;
        $pesan = "Pesanan dengan kode $kode_pemesanan telah dibatalkan oleh pelanggan.";
        if (!empty($alasan_pembatalan)) {
            $pesan .= " Alasan: $alasan_pembatalan";
        }
        $notif->createAdminNotification($judul, $pesan, 'pembatalan');
        
        // Commit transaksi
        $conn->commit();
        
        $_SESSION['flash_message'] = "Pesanan berhasil dibatalkan";
        $_SESSION['flash_type'] = "green";
        header("Location: " . USER_URL . "pesanan.php");
        exit;
        
    } catch (PDOException $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollBack();
        
        $_SESSION['flash_message'] = "Gagal membatalkan pesanan: " . $e->getMessage();
        $_SESSION['flash_type'] = "red";
        header("Location: " . USER_URL . "pesanan.php");
        exit;
    }
}

// Ambil filter
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Ambil daftar pemesanan
try {
    $sql = "SELECT p.*, m.merk, m.model, m.nomor_plat, m.foto_mobil 
            FROM pemesanan p
            JOIN mobil m ON p.mobil_id = m.id
            WHERE p.user_id = ?";
    $params = [$user_id];
    
    // Terapkan filter status jika ada
    if (!empty($status)) {
        $sql .= " AND p.status_pemesanan = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $pemesananList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

// Fungsi untuk mendapatkan label status
function getStatusLabel($status) {    
    switch ($status) {        
        case 'menunggu':            
            return '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-clock mr-1 text-yellow-600"></i>Menunggu Pembayaran</span>';
        case 'dibayar':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-money-bill-wave mr-1 text-green-600"></i>Dibayar</span>';        
        case 'dikonfirmasi':            
            return '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-check-circle mr-1 text-blue-600"></i>Dikonfirmasi</span>';        
        case 'berjalan':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-car mr-1 text-green-600"></i>Berjalan</span>';        
        case 'pending_return':            
            return '<span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-hourglass-half mr-1 text-indigo-600"></i>Menunggu Konfirmasi Pengembalian</span>';
        case 'selesai':            
            return '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-check-double mr-1 text-green-600"></i>Selesai</span>';        
        case 'dibatalkan':            
            return '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-times-circle mr-1 text-red-600"></i>Dibatalkan</span>';
        default:            
            return '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium inline-flex items-center"><i class="fas fa-question-circle mr-1 text-gray-600"></i>' . ucfirst(str_replace('_', ' ', $status ?? '')) . '</span>';
    }
}

// Setelah semua pemrosesan selesai, baru include header.php
require_once 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-3 border-b border-gray-200">
    <div class="container mx-auto px-6">
        <div class="flex text-sm">
            <a href="<?= USER_URL ?>" class="text-blue-600 hover:text-blue-800 transition-colors">Beranda</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-600 font-medium">Pesanan Saya</span>
        </div>
    </div>
</div>

<!-- Pemesanan List Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-6">
        <!-- Header dengan statistik ringkas -->
        <div class="bg-white rounded-xl shadow-md p-8 border border-gray-200 mb-8 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-50 rounded-full -mt-32 -mr-32 opacity-30"></div>
            <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-indigo-50 rounded-full opacity-30"></div>
            <div class="relative z-10">
                <h1 class="text-3xl font-bold text-gray-800 mb-4 flex items-center">
                    <span class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg p-2 mr-4 text-white shadow-md">
                        <i class="fas fa-car-side text-2xl"></i>
                    </span>
                    Pesanan Saya
                </h1>
                <p class="text-gray-600 mb-8 max-w-3xl">Kelola dan pantau semua pesanan rental mobil Anda di satu tempat. Lihat status, detail, dan riwayat pemesanan Anda.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <?php
                    // Hitung jumlah pesanan berdasarkan status
                    $statusCounts = [
                        'total' => 0,
                        'menunggu' => 0,
                        'berjalan' => 0,
                        'selesai' => 0
                    ];
                    
                    foreach ($pemesananList as $item) {
                        $statusCounts['total']++;
                        if (isset($statusCounts[$item['status_pemesanan']])) {
                            $statusCounts[$item['status_pemesanan']]++;
                        }
                    }
                    ?>
                    
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200 flex items-center hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 group">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center mr-4 shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-calendar-check text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-blue-800"><?= $statusCounts['total'] ?></h3>
                            <p class="text-sm font-medium text-blue-600">Total Pesanan</p>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-5 border border-yellow-200 flex items-center hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 group">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-r from-yellow-500 to-amber-500 flex items-center justify-center mr-4 shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-clock text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-yellow-800"><?= $statusCounts['menunggu'] ?></h3>
                            <p class="text-sm font-medium text-yellow-600">Menunggu Pembayaran</p>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 flex items-center hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 group">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mr-4 shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-car-side text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-green-800"><?= $statusCounts['berjalan'] ?></h3>
                            <p class="text-sm font-medium text-green-600">Sedang Berjalan</p>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border border-purple-200 flex items-center hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 group">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center mr-4 shadow-md group-hover:scale-110 transition-transform">
                            <i class="fas fa-check-double text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-purple-800"><?= $statusCounts['selesai'] ?></h3>
                            <p class="text-sm font-medium text-purple-600">Pesanan Selesai</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200 mb-8 transition-all hover:shadow-lg">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-filter text-blue-600 mr-2"></i> Filter Pesanan
            </h3>
            <form action="" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[250px]">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status Pesanan</label>
                    <div class="relative">
                        <select id="status" name="status" class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none shadow-sm">
                            <option value="">Semua Status</option>                  
                            <option value="menunggu" <?= $status === 'menunggu' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                            <option value="dibayar" <?= $status === 'dibayar' ? 'selected' : '' ?>>Dibayar</option>
                            <option value="dikonfirmasi" <?= $status === 'dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                            <option value="berjalan" <?= $status === 'berjalan' ? 'selected' : '' ?>>Berjalan</option>
                            <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="dibatalkan" <?= $status === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-blue-600">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-end space-x-3">
                    <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all flex items-center shadow-md hover:shadow-lg">
                        <i class="fas fa-search mr-2"></i> Terapkan Filter
                    </button>
                    
                    <?php if (!empty($status)): ?>
                    <a href="<?= USER_URL ?>pesanan.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg transition-all flex items-center border border-gray-300 hover:border-gray-400">
                        <i class="fas fa-sync-alt mr-2"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 shadow-sm animate-bounce">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                    <p>Terjadi kesalahan: <?= $error_message ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Pemesanan List -->
        <?php if (empty($pemesananList)): ?>
            <div class="text-center py-16 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200">
                <div class="flex flex-col items-center justify-center">
                    <div class="w-28 h-28 flex items-center justify-center rounded-full bg-blue-50 text-blue-500 mb-6 relative overflow-hidden">
                        <div class="animate-ping absolute inset-0 bg-blue-400 rounded-full opacity-30"></div>
                        <i class="fas fa-calendar-alt text-5xl relative z-10"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Belum Ada Pesanan</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">Anda belum memiliki riwayat pemesanan mobil. Mulai pesan mobil sekarang untuk perjalanan Anda!</p>
                    <a href="<?= USER_URL ?>mobil.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-all inline-flex items-center justify-center shadow-md hover:shadow-lg group">
                        <i class="fas fa-car mr-2 group-hover:animate-bounce"></i> Lihat Katalog Mobil
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($pemesananList as $pesanan): ?>
                    <div class="mb-6 group">
                        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden group-hover:shadow-lg transition-all duration-300">
                            <!-- Header Pesanan -->
                            <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex flex-col sm:flex-row justify-between sm:items-center">
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-500 mb-1">Kode Pemesanan:</span>
                                    <div class="flex items-center">
                                        <span class="font-bold text-gray-800"><?= $pesanan['kode_pemesanan'] ?></span>
                                        <button onclick="copyToClipboard('<?= $pesanan['kode_pemesanan'] ?>')" class="ml-2 text-blue-500 hover:text-blue-700 focus:outline-none" title="Salin Kode">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center mt-2 sm:mt-0">
                                    <?= getStatusLabel($pesanan['status_pemesanan']) ?>
                                    <span class="ml-4 text-sm text-gray-500">
                                        <i class="far fa-calendar-alt mr-1"></i> 
                                        <?= date('d M Y', strtotime($pesanan['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-5">
                                <div class="flex flex-col md:flex-row">
                                    <div class="w-full md:w-1/3 mb-4 md:mb-0 md:pr-6">
                                        <div class="bg-gray-100 rounded-xl overflow-hidden h-48 flex items-center justify-center">
                                            <?php if (!empty($pesanan['foto_mobil'])): ?>
                                                <img src="<?= ASSETS_URL ?>uploads/mobil/<?= $pesanan['foto_mobil'] ?>" 
                                                     alt="<?= $pesanan['merk'] ?> <?= $pesanan['model'] ?>" 
                                                     class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                                            <?php else: ?>
                                                <div class="flex flex-col items-center justify-center">
                                                    <i class="fas fa-car-side text-5xl text-gray-400 mb-2"></i>
                                                    <p class="text-sm text-gray-500">Foto tidak tersedia</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php 
                                        // Tampilkan fitur mobil jika tersedia
                                        if (!empty($pesanan['fitur'])) {
                                            $fiturJson = json_decode($pesanan['fitur'], true);
                                            if (!empty($fiturJson)) {
                                                echo '<div class="mt-3 bg-blue-50 p-3 rounded-lg border border-blue-100">';
                                                echo '<h4 class="text-sm font-semibold text-blue-700 mb-2"><i class="fas fa-list-check mr-1"></i> Fitur Mobil:</h4>';
                                                echo '<div class="flex flex-wrap gap-2">';
                                                
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
                                                
                                                $shownFeatures = 0;
                                                foreach ($fiturJson as $fiturKey) {
                                                    if (isset($fiturMapping[$fiturKey]) && $shownFeatures < 5) {
                                                        echo '<span class="inline-flex items-center bg-white px-2 py-1 rounded text-xs font-medium text-gray-700 border border-blue-100">';
                                                        echo '<i class="fas fa-' . $fiturMapping[$fiturKey]['icon'] . ' text-blue-500 mr-1"></i> ';
                                                        echo $fiturMapping[$fiturKey]['label'];
                                                        echo '</span>';
                                                        $shownFeatures++;
                                                    }
                                                }
                                                
                                                if (count($fiturJson) > 5) {
                                                    echo '<span class="inline-flex items-center bg-blue-100 px-2 py-1 rounded text-xs font-medium text-blue-700">';
                                                    echo '+' . (count($fiturJson) - 5) . ' lainnya';
                                                    echo '</span>';
                                                }
                                                
                                                echo '</div></div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Detail Pemesanan -->
                                    <div class="md:col-span-8 lg:col-span-9 p-6">
                                        <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4">
                                            <div>
                                                <h2 class="text-xl font-bold text-gray-800 mb-1 hover:text-blue-600 transition-colors"><?= $pesanan['merk'] ?> <?= $pesanan['model'] ?></h2>
                                                <p class="text-gray-500 text-sm"><i class="fas fa-id-card mr-1"></i> <?= $pesanan['nomor_plat'] ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-100">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Mulai Sewa</span>
                                                    <div class="flex items-center mt-1 text-gray-700">
                                                        <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                                        <span class="font-medium"><?= date('d F Y', strtotime($pesanan['tanggal_mulai'])) ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Selesai Sewa</span>
                                                    <div class="flex items-center mt-1 text-gray-700">
                                                        <i class="fas fa-calendar-check mr-2 text-green-500"></i>
                                                        <span class="font-medium"><?= date('d F Y', strtotime($pesanan['tanggal_selesai'])) ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Durasi Sewa</span>
                                                    <div class="flex items-center mt-1 text-gray-700">
                                                        <i class="fas fa-clock mr-2 text-purple-500"></i>
                                                        <?php
                                                        // Hitung durasi dalam hari
                                                        $tanggal_mulai = new DateTime($pesanan['tanggal_mulai']);
                                                        $tanggal_selesai = new DateTime($pesanan['tanggal_selesai']);
                                                        $durasi = $tanggal_selesai->diff($tanggal_mulai)->days;
                                                        ?>
                                                        <span class="font-medium"><?= $durasi ?> hari</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex justify-between items-center mb-4">
                                            <div class="flex items-baseline">
                                                <span class="text-sm text-gray-600 mr-2">Total Pembayaran:</span>
                                                <span class="text-xl font-bold text-blue-600">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-2 pt-3 border-t border-gray-100">
                                            <?php if ($pesanan['status_pemesanan'] === 'menunggu'): ?>
                                                <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $pesanan['kode_pemesanan'] ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all text-center flex items-center justify-center shadow-sm hover:shadow-md">
                                                    <i class="fas fa-credit-card mr-2"></i> Bayar Sekarang
                                                </a>
                                                <button type="button" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-all text-center flex items-center justify-center btn-cancel shadow-sm hover:shadow-md" data-kode="<?= $pesanan['kode_pemesanan'] ?>">
                                                    <i class="fas fa-times-circle mr-2"></i> Batalkan
                                                </button>
                                            <?php elseif ($pesanan['status_pemesanan'] === 'dikonfirmasi'): ?>
                                                <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $pesanan['kode_pemesanan'] ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all text-center flex items-center justify-center shadow-sm hover:shadow-md">
                                                    <i class="fas fa-eye mr-2"></i> Lihat Detail
                                                </a>
                                                <button type="button" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-all text-center flex items-center justify-center btn-cancel shadow-sm hover:shadow-md" data-kode="<?= $pesanan['kode_pemesanan'] ?>">
                                                    <i class="fas fa-times-circle mr-2"></i> Batalkan
                                                </button>
                                            <?php elseif ($pesanan['status_pemesanan'] === 'berjalan'): ?>
                                                <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $pesanan['kode_pemesanan'] ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all text-center flex items-center justify-center shadow-sm hover:shadow-md">
                                                    <i class="fas fa-eye mr-2"></i> Lihat Detail
                                                </a>
                                                <?php if ($pesanan['status_pemesanan'] === 'berjalan'): ?>
                                                <button type="button" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-all text-center flex items-center justify-center btn-return shadow-sm hover:shadow-md" data-kode="<?= $pesanan['kode_pemesanan'] ?>">
                                                    <i class="fas fa-car-side mr-2"></i> Kembalikan Mobil
                                                </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $pesanan['kode_pemesanan'] ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all text-center flex items-center justify-center shadow-sm hover:shadow-md">
                                                    <i class="fas fa-eye mr-2"></i> Lihat Detail
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination Placeholder - can be implemented if needed -->
            <div class="mt-6 flex justify-center">
                <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg mx-1 transition-colors duration-200 hidden">
                    <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                </button>
                <span class="bg-blue-600 text-white font-medium py-2 px-4 rounded-lg mx-1 transition-colors duration-200">1</span>
                <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg mx-1 transition-colors duration-200 hidden">
                    Selanjutnya <i class="fas fa-chevron-right ml-1"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Pembatalan -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden transition-opacity duration-300">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
        <div class="p-6">
            <div class="flex justify-between items-center border-b border-gray-200 pb-3 mb-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    Konfirmasi Pembatalan
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-4 bg-red-50 text-red-700 p-3 rounded-lg border border-red-200">
                <p><i class="fas fa-info-circle mr-2"></i> Apakah Anda yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            
            <form id="cancelForm" action="" method="POST">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="kode_pemesanan" id="kode_pemesanan" value="">
                
                <div class="mb-4">
                    <label for="alasan_pembatalan" class="block text-sm font-medium text-gray-700 mb-1">Alasan Pembatalan (Opsional)</label>
                    <textarea id="alasan_pembatalan" name="alasan_pembatalan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors" placeholder="Tuliskan alasan pembatalan Anda di sini..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-all shadow-sm text-gray-600" id="cancelButton">
                        <i class="fas fa-times mr-2"></i> Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all shadow-sm">
                        <i class="fas fa-check mr-2"></i> Ya, Batalkan Pesanan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pengembalian Mobil -->
<div id="returnModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden transition-opacity duration-300">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="returnModalContent">
        <div class="p-6">
            <div class="flex justify-between items-center border-b border-gray-200 pb-3 mb-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-car-side text-green-500 mr-2"></i>
                    Konfirmasi Pengembalian Mobil
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" id="closeReturnModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-4 bg-blue-50 text-blue-700 p-3 rounded-lg border border-blue-200">
                <p><i class="fas fa-info-circle mr-2"></i> Konfirmasi pengembalian mobil Anda. Admin akan memeriksa kondisi mobil dan menyelesaikan proses pengembalian.</p>
            </div>
            
            <form id="returnForm" action="" method="POST">
                <input type="hidden" name="action" value="return">
                <input type="hidden" name="kode_pemesanan" id="return_kode_pemesanan" value="">
                
                <div class="mb-4">
                    <label for="kondisi_mobil" class="block text-sm font-medium text-gray-700 mb-1">Kondisi Mobil Saat Ini</label>
                    <textarea id="kondisi_mobil" name="kondisi_mobil" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors" placeholder="Deskripsikan kondisi mobil saat ini (opsional)..."></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="catatan_tambahan" class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                    <textarea id="catatan_tambahan" name="catatan_tambahan" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors" placeholder="Informasi tambahan yang perlu diketahui admin..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-all shadow-sm text-gray-600" id="cancelReturnButton">
                        <i class="fas fa-times mr-2"></i> Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all shadow-sm">
                        <i class="fas fa-check mr-2"></i> Konfirmasi Pengembalian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CTA Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-6">
        <div class="bg-gradient-to-r from-blue-700 to-blue-500 rounded-xl p-8 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-48 h-48 bg-white opacity-10 rounded-full -mt-16 -mr-16"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white opacity-10 rounded-full -mb-16 -ml-16"></div>
            <div class="relative z-10 text-center text-white">
                <h2 class="text-2xl font-bold mb-3">Butuh mobil untuk perjalanan Anda?</h2>
                <p class="text-lg opacity-90 mb-6 max-w-3xl mx-auto">Kami memiliki berbagai pilihan mobil sesuai kebutuhan Anda. Pesan sekarang dan nikmati perjalanan yang nyaman!</p>
                <a href="<?= USER_URL ?>mobil.php" class="bg-white text-blue-600 font-semibold py-3 px-8 rounded-lg hover:bg-blue-50 transition duration-300 inline-flex items-center justify-center shadow-md hover:shadow-lg group">
                    <i class="fas fa-car-side mr-2 group-hover:animate-bounce"></i> Lihat Katalog Mobil
                </a>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal pembatalan
    const cancelButtons = document.querySelectorAll('.btn-cancel');
    const cancelModal = document.getElementById('cancelModal');
    const modalContent = document.getElementById('modalContent');
    const closeModal = document.getElementById('closeModal');
    const cancelButton = document.getElementById('cancelButton');
    const kodeInput = document.getElementById('kode_pemesanan');
    
    // Modal pengembalian
    const returnButtons = document.querySelectorAll('.btn-return');
    const returnModal = document.getElementById('returnModal');
    const returnModalContent = document.getElementById('returnModalContent');
    const closeReturnModal = document.getElementById('closeReturnModal');
    const cancelReturnButton = document.getElementById('cancelReturnButton');
    const returnKodeInput = document.getElementById('return_kode_pemesanan');
    
    // Tampilkan modal saat tombol batalkan diklik
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const kode = this.getAttribute('data-kode');
            kodeInput.value = kode;
            cancelModal.classList.remove('hidden');
            
            // Animasi fade in
            setTimeout(() => {
                modalContent.classList.add('scale-100', 'opacity-100');
                modalContent.classList.remove('scale-95', 'opacity-0');
            }, 50);
        });
    });
    
    // Tampilkan modal pengembalian saat tombol kembalikan diklik
    returnButtons.forEach(button => {
        button.addEventListener('click', function() {
            const kode = this.getAttribute('data-kode');
            returnKodeInput.value = kode;
            returnModal.classList.remove('hidden');
            
            // Animasi fade in
            setTimeout(() => {
                returnModalContent.classList.add('scale-100', 'opacity-100');
                returnModalContent.classList.remove('scale-95', 'opacity-0');
            }, 50);
        });
    });
    
    // Sembunyikan modal pembatalan
    const hideModal = () => {
        // Animasi fade out
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            cancelModal.classList.add('hidden');
        }, 300);
    };
    
    // Sembunyikan modal pengembalian
    const hideReturnModal = () => {
        // Animasi fade out
        returnModalContent.classList.remove('scale-100', 'opacity-100');
        returnModalContent.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            returnModal.classList.add('hidden');
        }, 300);
    };
    
    closeModal.addEventListener('click', hideModal);
    cancelButton.addEventListener('click', hideModal);
    
    closeReturnModal.addEventListener('click', hideReturnModal);
    cancelReturnButton.addEventListener('click', hideReturnModal);
    
    // Tutup modal saat klik di luar modal
    cancelModal.addEventListener('click', function(e) {
        if (e.target === this) {
            hideModal();
        }
    });
    
    // Tutup modal pengembalian saat klik di luar modal
    returnModal.addEventListener('click', function(e) {
        if (e.target === this) {
            hideReturnModal();
        }
    });
    
    // Animasi statistik
    const statCards = document.querySelectorAll('.bg-blue-50, .bg-yellow-50, .bg-green-50, .bg-purple-50');
    
    function animateStatCards() {
        statCards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('animate-fadeIn');
            }, index * 100);
        });
    }
    
    // Gunakan IntersectionObserver untuk menganimasikan saat di viewport
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateStatCards();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelector('.grid.grid-cols-1.md\\:grid-cols-4') && 
        observer.observe(document.querySelector('.grid.grid-cols-1.md\\:grid-cols-4'));
    } else {
        // Fallback untuk browser lama
        animateStatCards();
    }
    
    // Fungsi copy to clipboard
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Tampilkan notifikasi sukses
            const notification = document.createElement('div');
            notification.className = 'fixed bottom-4 right-4 bg-green-600 text-white py-2 px-4 rounded-lg shadow-lg z-50 animate-fadeIn';
            notification.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Kode berhasil disalin!';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('animate-fadeOut');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 500);
            }, 2000);
        }).catch(function(err) {
            console.error('Gagal menyalin: ', err);
        });
    }
});
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeOut {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(10px); }
}

.animate-fadeIn {
    animation: fadeIn 0.5s ease-out forwards;
}

.animate-fadeOut {
    animation: fadeOut 0.5s ease-out forwards;
}
</style>

<?php require_once 'includes/footer.php'; ?> 