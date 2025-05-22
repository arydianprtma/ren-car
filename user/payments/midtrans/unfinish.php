<?php
/**
 * File callback untuk pembayaran Midtrans yang tidak selesai
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../../../config/midtrans/config.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Anda harus login terlebih dahulu";
    $_SESSION['flash_type'] = "red";
    echo "<script>window.location.href = '" . USER_URL . "login.php';</script>";
    exit;
}

// Periksa apakah ada order_id
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    $_SESSION['flash_message'] = "ID pesanan tidak valid";
    $_SESSION['flash_type'] = "red";
    echo "<script>window.location.href = '" . USER_URL . "pesanan.php';</script>";
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Set flash message
$_SESSION['flash_message'] = "Pembayaran tidak diselesaikan. Anda dapat mencoba kembali saat siap.";
$_SESSION['flash_type'] = "yellow";

// Redirect ke halaman detail pemesanan
echo "<script>window.location.href = '" . USER_URL . "pemesanan_detail.php?kode=" . $order_id . "';</script>";
exit;
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-3">
    <div class="container mx-auto px-6">
        <div class="flex text-sm">
            <a href="<?= USER_URL ?>" class="text-blue-600 hover:text-blue-800">Beranda</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="<?= USER_URL ?>pesanan.php" class="text-blue-600 hover:text-blue-800">Pesanan Saya</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-600">Pembayaran Belum Selesai</span>
        </div>
    </div>
</div>

<!-- Unfinish Section -->
<section class="py-12">
    <div class="container mx-auto px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-200">
                <div class="flex justify-center mb-6">
                    <span class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-yellow-100">
                        <i class="fas fa-exclamation-circle text-yellow-500 text-5xl"></i>
                    </span>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Pembayaran Belum Selesai</h1>
                
                <p class="text-gray-600 mb-6">
                    Anda belum menyelesaikan proses pembayaran. Pesanan Anda tetap tersimpan dan Anda dapat melanjutkan pembayaran kapan saja.
                </p>
                
                <div class="mt-8">
                    <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $order_id ?>" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-all">
                        <i class="fas fa-chevron-left mr-2"></i> Kembali ke Detail Pesanan
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?> 