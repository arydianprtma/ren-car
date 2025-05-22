<?php
/**
 * Script perbaikan database untuk status_pemesanan kosong
 */
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth_check.php';

// Hanya admin yang boleh mengakses halaman ini
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['flash_message'] = "Akses ditolak. Anda tidak berhak mengakses halaman ini.";
    $_SESSION['flash_type'] = "red";
    header("Location: " . ADMIN_URL . "index.php");
    exit;
}

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Variabel untuk menyimpan hasil
$totalFixed = 0;
$errorMessage = '';
$isSuccess = false;

// Proses update jika form disubmit
if (isset($_POST['fix_database'])) {
    try {
        // Mulai transaksi
        $conn->beginTransaction();
        
        // 1. Update status_pemesanan yang NULL menjadi 'menunggu'
        $stmt = $conn->prepare("UPDATE pemesanan SET status_pemesanan = 'menunggu' WHERE status_pemesanan IS NULL OR status_pemesanan = ''");
        $stmt->execute();
        $nullStatusFixed = $stmt->rowCount();
        
        // 2. Periksa pemesanan dengan metode_pembayaran yang sudah dipilih tapi status masih 'menunggu'
        $stmt = $conn->prepare("UPDATE pemesanan SET status_pemesanan = 'dibayar' 
                               WHERE metode_pembayaran IS NOT NULL AND metode_pembayaran != '' 
                               AND status_pemesanan = 'menunggu'");
        $stmt->execute();
        $paymentMethodFixed = $stmt->rowCount();
        
        // Commit transaksi
        $conn->commit();
        
        // Set pesan berhasil
        $totalFixed = $nullStatusFixed + $paymentMethodFixed;
        $isSuccess = true;
        
    } catch (PDOException $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollback();
        $errorMessage = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Cek jumlah data yang perlu diperbaiki
$needsFixing = 0;
try {
    // Hitung pemesanan dengan status NULL atau kosong
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pemesanan WHERE status_pemesanan IS NULL OR status_pemesanan = ''");
    $stmt->execute();
    $nullStatus = $stmt->fetchColumn();
    
    // Hitung pemesanan dengan metode_pembayaran tapi status masih 'menunggu'
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pemesanan 
                           WHERE metode_pembayaran IS NOT NULL AND metode_pembayaran != '' 
                           AND status_pemesanan = 'menunggu'");
    $stmt->execute();
    $incorrectStatus = $stmt->fetchColumn();
    
    $needsFixing = $nullStatus + $incorrectStatus;
    
} catch (PDOException $e) {
    $errorMessage = "Terjadi kesalahan saat memeriksa database: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaikan Database - Admin Panel</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/tailwind.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-10">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-database mr-3 text-primary-600"></i> Perbaikan Database
                </h1>
                <p class="text-sm text-gray-600">Memperbaiki status_pemesanan yang kosong atau tidak sesuai</p>
            </div>
            <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg">
                <p class="font-bold">Error</p>
                <p><?= $errorMessage ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($isSuccess): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
                <p class="font-bold">Berhasil!</p>
                <p>Total <?= $totalFixed ?> data pemesanan berhasil diperbaiki.</p>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Status Database</h2>
            
            <?php if ($needsFixing > 0): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r-lg">
                    <p class="font-semibold text-yellow-800">Ditemukan <?= $needsFixing ?> data yang perlu diperbaiki:</p>
                    <ul class="list-disc pl-5 mt-2 text-yellow-700">
                        <li>Pemesanan dengan status kosong: <?= $nullStatus ?></li>
                        <li>Pemesanan dengan metode pembayaran tapi status masih 'menunggu': <?= $incorrectStatus ?></li>
                    </ul>
                </div>
                
                <form method="POST" action="">
                    <p class="text-gray-600 mb-4">Klik tombol di bawah untuk memperbaiki semua masalah database yang terdeteksi:</p>
                    <button type="submit" name="fix_database" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-tools mr-2"></i> Perbaiki Database
                    </button>
                </form>
            <?php else: ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                    <p class="font-semibold text-green-800">Semua data pemesanan sudah benar!</p>
                    <p class="text-green-700 mt-1">Tidak ada masalah status_pemesanan yang terdeteksi dalam database.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Penjelasan Perbaikan</h2>
            
            <p class="text-gray-600 mb-3">Script ini akan melakukan perbaikan sebagai berikut:</p>
            
            <ol class="list-decimal pl-5 space-y-2 text-gray-600">
                <li>Mengubah semua status_pemesanan yang kosong (NULL atau string kosong) menjadi 'menunggu'</li>
                <li>Mengubah status menjadi 'dibayar' untuk pemesanan yang sudah memiliki metode_pembayaran tetapi statusnya masih 'menunggu'</li>
            </ol>
            
            <div class="mt-6 pt-4 border-t border-gray-100">
                <p class="text-sm text-gray-500">Catatan: Semua perubahan database dilakukan dalam satu transaksi untuk memastikan integritas data.</p>
            </div>
        </div>
    </div>
</body>
</html> 