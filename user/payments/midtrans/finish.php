<?php
/**
 * File callback untuk pembayaran Midtrans yang berhasil atau pending
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

// Periksa apakah ada order_id dan status
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    $_SESSION['flash_message'] = "ID pesanan tidak valid";
    $_SESSION['flash_type'] = "red";
    echo "<script>window.location.href = '" . USER_URL . "pesanan.php';</script>";
    exit;
}

$order_id = $_GET['order_id'];
$midtrans_order_id = $_GET['midtrans_order_id'] ?? '';
$payment_status = $_GET['status'] ?? 'success';
$user_id = $_SESSION['user_id'];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil data pemesanan
try {
    $stmt = $conn->prepare("SELECT p.*, m.merk, m.model 
                           FROM pemesanan p
                           JOIN mobil m ON p.mobil_id = m.id
                           WHERE p.kode_pemesanan = ? AND p.user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $pemesanan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika pemesanan tidak ditemukan
    if (!$pemesanan) {
        $_SESSION['flash_message'] = "Pemesanan tidak ditemukan";
        $_SESSION['flash_type'] = "red";
        echo "<script>window.location.href = '" . USER_URL . "pesanan.php';</script>";
        exit;
    }

    // Update status pemesanan berdasarkan payment_status
    $status_pemesanan = ($payment_status == 'success') ? 'dikonfirmasi' : 'menunggu';
    $metode_pembayaran = 'midtrans';
    
    // Update status pemesanan
    $stmt = $conn->prepare("UPDATE pemesanan SET 
                           status_pemesanan = :status, 
                           metode_pembayaran = :metode,
                           midtrans_status = :midtrans_status,
                           updated_at = NOW()
                           WHERE kode_pemesanan = :kode AND user_id = :user_id");
    
    $stmt->bindParam(':status', $status_pemesanan, PDO::PARAM_STR);
    $stmt->bindParam(':metode', $metode_pembayaran, PDO::PARAM_STR);
    $stmt->bindParam(':midtrans_status', $payment_status, PDO::PARAM_STR);
    $stmt->bindParam(':kode', $order_id, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Update pemesanan dengan midtrans_order_id jika belum diupdate
    if (!empty($midtrans_order_id)) {
        $check_stmt = $conn->prepare("SELECT midtrans_order_id FROM pemesanan WHERE kode_pemesanan = ?");
        $check_stmt->execute([$order_id]);
        $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (empty($check_result['midtrans_order_id'])) {
            $update_stmt = $conn->prepare("UPDATE pemesanan SET midtrans_order_id = ? WHERE kode_pemesanan = ?");
            $update_stmt->execute([$midtrans_order_id, $order_id]);
        }
    }
    
    // Set flash message
    if ($payment_status == 'success') {
        $_SESSION['flash_message'] = "Pembayaran berhasil! Status pemesanan telah diperbarui menjadi Dikonfirmasi.";
        $_SESSION['flash_type'] = "green";
    } else {
        $_SESSION['flash_message'] = "Pembayaran dalam proses! Status pemesanan masih Menunggu sampai pembayaran selesai.";
        $_SESSION['flash_type'] = "blue";
    }
    
    // Redirect ke halaman detail pemesanan
    echo "<script>window.location.href = '" . USER_URL . "pemesanan_detail.php?kode=" . $order_id . "';</script>";
    exit;
    
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    echo "<script>window.location.href = '" . USER_URL . "pesanan.php';</script>";
    exit;
}
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-3">
    <div class="container mx-auto px-6">
        <div class="flex text-sm">
            <a href="<?= USER_URL ?>" class="text-blue-600 hover:text-blue-800">Beranda</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="<?= USER_URL ?>pesanan.php" class="text-blue-600 hover:text-blue-800">Pesanan Saya</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-600">Pembayaran Berhasil</span>
        </div>
    </div>
</div>

<!-- Success Section -->
<section class="py-12">
    <div class="container mx-auto px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-200">
                <div class="flex justify-center mb-6">
                    <span class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-green-100">
                        <i class="fas fa-check-circle text-green-500 text-5xl"></i>
                    </span>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Pembayaran Berhasil</h1>
                
                <p class="text-gray-600 mb-6">
                    Pembayaran Anda sedang diproses. Anda akan menerima konfirmasi segera setelah proses verifikasi selesai.
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