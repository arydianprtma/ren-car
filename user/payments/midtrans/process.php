<?php
/**
 * File untuk memproses pembayaran melalui Midtrans
 */
// Load config dan database langsung dari root
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/midtrans/config.php';

// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Anda harus login terlebih dahulu";
    $_SESSION['flash_type'] = "red";
    
    // Gunakan JavaScript redirect untuk menghindari header already sent
    echo "<script>window.location.href = '" . USER_URL . "login.php';</script>";
    exit;
}

// Periksa apakah ada kode pemesanan
if (!isset($_GET['kode']) || empty($_GET['kode'])) {
    $_SESSION['flash_message'] = "Kode pemesanan tidak valid";
    $_SESSION['flash_type'] = "red";
    
    // Gunakan JavaScript redirect untuk menghindari header already sent
    echo "<script>window.location.href = '" . USER_URL . "pesanan.php';</script>";
    exit;
}

$kode_pemesanan = $_GET['kode'];
$user_id = $_SESSION['user_id'];

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil data pemesanan
try {
    $stmt = $conn->prepare("SELECT p.*, m.merk, m.model, m.nomor_plat, m.foto_mobil, 
                           u.nama as nama_user, u.email, u.no_telp, u.alamat
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
        
        // Gunakan JavaScript redirect untuk menghindari header already sent
        echo "<script>window.location.href = '" . USER_URL . "pesanan.php';</script>";
        exit;
    }

    // Periksa jika status pemesanan tidak 'menunggu'
    if ($pemesanan['status_pemesanan'] !== 'menunggu') {
        $_SESSION['flash_message'] = "Pemesanan ini sudah dibayar atau diproses";
        $_SESSION['flash_type'] = "red";
        
        // Gunakan JavaScript redirect untuk menghindari header already sent
        echo "<script>window.location.href = '" . USER_URL . "pemesanan_detail.php?kode=" . $kode_pemesanan . "';</script>";
        exit;
    }

    // Hitung durasi sewa
    $tanggal_mulai = new DateTime($pemesanan['tanggal_mulai']);
    $tanggal_selesai = new DateTime($pemesanan['tanggal_selesai']);
    $durasi = $tanggal_mulai->diff($tanggal_selesai)->days;

} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Terjadi kesalahan: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    
    // Gunakan JavaScript redirect untuk menghindari header already sent
    echo "<script>window.location.href = '" . USER_URL . "pesanan.php';</script>";
    exit;
}

// Siapkan data untuk Midtrans
$timestamp = time();
$unique_order_id = $kode_pemesanan . '-' . $timestamp;

$transaction_details = [
    'order_id' => $unique_order_id,
    'gross_amount' => (int)$pemesanan['total_harga']
];

$item_details = [
    [
        'id' => $pemesanan['mobil_id'],
        'price' => (int)($pemesanan['total_harga'] / $durasi),
        'quantity' => $durasi,
        'name' => $pemesanan['merk'] . ' ' . $pemesanan['model'] . ' (' . $pemesanan['nomor_plat'] . ')'
    ]
];

$customer_details = [
    'first_name' => $pemesanan['nama_user'],
    'email' => $pemesanan['email'],
    'phone' => $pemesanan['no_telp'],
    'billing_address' => [
        'address' => $pemesanan['alamat'] ?? 'Alamat tidak tersedia',
        'country_code' => 'IDN'
    ]
];

// Siapkan data transaksi
$transaction_data = [
    'transaction_details' => $transaction_details,
    'item_details' => $item_details,
    'customer_details' => $customer_details,
    'enabled_payments' => MIDTRANS_ENABLED_PAYMENTS,
    'callbacks' => [
        'finish' => MIDTRANS_FINISH_URL . '?order_id=' . $kode_pemesanan . '&midtrans_order_id=' . $unique_order_id,
        'error' => MIDTRANS_ERROR_URL . '?order_id=' . $kode_pemesanan . '&midtrans_order_id=' . $unique_order_id,
        'unfinish' => MIDTRANS_UNFINISH_URL . '?order_id=' . $kode_pemesanan . '&midtrans_order_id=' . $unique_order_id,
    ]
];

// Buat transaksi dengan Midtrans API
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => MIDTRANS_SNAP_API_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($transaction_data),
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    $_SESSION['flash_message'] = "Gagal membuat transaksi: " . $err;
    $_SESSION['flash_type'] = "red";
    
    // Gunakan JavaScript redirect untuk menghindari header already sent
    echo "<script>window.location.href = '" . USER_URL . "pemesanan_detail.php?kode=" . $kode_pemesanan . "';</script>";
    exit;
}

$responseData = json_decode($response, true);
$snapToken = $responseData['token'] ?? '';
$redirect_url = $responseData['redirect_url'] ?? '';

if (empty($snapToken)) {
    $errorMessage = "Gagal mendapatkan token pembayaran";
    if (isset($responseData['error_messages'])) {
        $errorMessage .= ": " . implode(", ", $responseData['error_messages']);
    }
    
    $_SESSION['flash_message'] = $errorMessage;
    $_SESSION['flash_type'] = "red";
    
    // Gunakan JavaScript redirect untuk menghindari header already sent
    echo "<script>window.location.href = '" . USER_URL . "pemesanan_detail.php?kode=" . $kode_pemesanan . "';</script>";
    exit;
}

// Update pemesanan dengan token Midtrans
try {
    $stmt = $conn->prepare("UPDATE pemesanan SET
                           midtrans_token = :token,
                           midtrans_order_id = :midtrans_order_id,
                           updated_at = NOW()
                           WHERE kode_pemesanan = :kode");
    $stmt->bindParam(':token', $snapToken, PDO::PARAM_STR);
    $stmt->bindParam(':midtrans_order_id', $unique_order_id, PDO::PARAM_STR);
    $stmt->bindParam(':kode', $kode_pemesanan, PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Gagal menyimpan token pembayaran: " . $e->getMessage();
    $_SESSION['flash_type'] = "red";
    
    // Gunakan JavaScript redirect untuk menghindari header already sent
    echo "<script>window.location.href = '" . USER_URL . "pemesanan_detail.php?kode=" . $kode_pemesanan . "';</script>";
    exit;
}

// Tidak lagi menggunakan include header, sebagai gantinya kita buat HTML sendiri
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Rental Mobil</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Midtrans Snap -->
    <script src="<?= MIDTRANS_SNAP_URL ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
    <!-- Additional Styles -->
    <style>
        /* Dropdown styling */
        .dropdown-menu {
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s, transform 0.3s;
            visibility: hidden;
        }
        
        .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
            visibility: visible !important;
        }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="<?= USER_URL ?>" class="flex items-center">
                        <i class="fas fa-car-side text-blue-600 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-blue-600">Rental Mobil</span>
                    </a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="<?= USER_URL ?>" class="text-gray-700 hover:text-blue-500 transition duration-300">Beranda</a>
                    <a href="<?= USER_URL ?>mobil.php" class="text-gray-700 hover:text-blue-500 transition duration-300">Mobil</a>
                    <a href="<?= USER_URL ?>tentang.php" class="text-gray-700 hover:text-blue-500 transition duration-300">Tentang Kami</a>
                    <a href="<?= USER_URL ?>kontak.php" class="text-gray-700 hover:text-blue-500 transition duration-300">Kontak</a>
                    
                    <?php if(isLoggedIn()): ?>
                        <div class="relative dropdown">
                            <button id="userDropdown" class="flex items-center text-gray-700 hover:text-blue-500 transition px-3 py-1 rounded-full border border-transparent hover:border-blue-100 hover:bg-blue-50">
                                <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                                <span class="mr-1"><?= $_SESSION['user_nama'] ?? 'User' ?></span>
                                <svg class="h-4 w-4 fill-current text-gray-500 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                </svg>
                            </button>
                            <div id="userMenu" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-100">
                                <a href="<?= USER_URL ?>profil.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                                    <i class="fas fa-user-edit mr-2"></i> Profil
                                </a>
                                <a href="<?= USER_URL ?>pesanan.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                                    <i class="fas fa-shopping-bag mr-2"></i> Pesanan Saya
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="<?= USER_URL ?>logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-500 hover:text-white">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= USER_URL ?>login.php" class="text-gray-700 hover:text-blue-500 transition duration-300">Login</a>
                        <a href="<?= USER_URL ?>register.php" class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if(isset($_SESSION['flash_message'])): ?>
    <div class="container mx-auto px-6 py-3">
        <div class="bg-<?= $_SESSION['flash_type'] ?? 'green' ?>-100 border border-<?= $_SESSION['flash_type'] ?? 'green' ?>-400 text-<?= $_SESSION['flash_type'] ?? 'green' ?>-700 px-4 py-3 rounded-lg relative flex items-center" role="alert">
            <i class="fas fa-<?= $_SESSION['flash_type'] == 'red' ? 'exclamation-circle' : 'check-circle' ?> mr-3"></i>
            <span class="block sm:inline"><?= $_SESSION['flash_message'] ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                <svg class="fill-current h-6 w-6 text-<?= $_SESSION['flash_type'] ?? 'green' ?>-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    </div>
    <?php endif; ?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-3">
    <div class="container mx-auto px-6">
        <div class="flex text-sm">
            <a href="<?= USER_URL ?>" class="text-blue-600 hover:text-blue-800">Beranda</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="<?= USER_URL ?>pesanan.php" class="text-blue-600 hover:text-blue-800">Pesanan Saya</a>
            <span class="mx-2 text-gray-500">/</span>
            <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $kode_pemesanan ?>" class="text-blue-600 hover:text-blue-800">Detail Pesanan</a>
            <span class="mx-2 text-gray-500">/</span>
            <span class="text-gray-600">Pembayaran</span>
        </div>
    </div>
</div>

<!-- Pembayaran Section -->
<section class="py-12">
    <div class="container mx-auto px-6">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pembayaran Pesanan #<?= $kode_pemesanan ?></h1>
            
            <!-- Detail Pemesanan -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Pemesanan</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-gray-600 text-sm mb-2">Mobil:</p>
                        <p class="font-medium"><?= $pemesanan['merk'] ?> <?= $pemesanan['model'] ?> (<?= $pemesanan['nomor_plat'] ?>)</p>
                    </div>
                    
                    <div>
                        <p class="text-gray-600 text-sm mb-2">Tanggal Sewa:</p>
                        <p class="font-medium"><?= date('d F Y', strtotime($pemesanan['tanggal_mulai'])) ?> - <?= date('d F Y', strtotime($pemesanan['tanggal_selesai'])) ?> (<?= $durasi ?> hari)</p>
                    </div>
                    
                    <div>
                        <p class="text-gray-600 text-sm mb-2">Total Biaya:</p>
                        <p class="font-bold text-lg text-blue-600">Rp <?= number_format($pemesanan['total_harga'], 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Pembayaran -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Metode Pembayaran</h2>
                
                <div id="payment-options" class="text-center">
                    <p class="text-gray-600 mb-6">Silakan pilih metode pembayaran melalui Midtrans.</p>
                    
                    <button id="pay-button" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-all">
                        <i class="fas fa-credit-card mr-2"></i> Bayar Sekarang
                    </button>
                    
                    <p class="text-sm text-gray-500 mt-4">Anda akan diarahkan ke halaman pembayaran yang aman.</p>
                </div>
                
                <div id="payment-processing" class="text-center hidden">
                    <div class="flex justify-center mb-4">
                        <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600 mb-2">Memproses pembayaran Anda...</p>
                    <p class="text-sm text-gray-500">Mohon jangan tutup halaman ini.</p>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <a href="<?= USER_URL ?>pemesanan_detail.php?kode=<?= $kode_pemesanan ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke detail pesanan
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-8 mt-auto">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-semibold mb-4 text-blue-400">Rental Mobil</h3>
                <p class="text-sm text-gray-400 mb-4">
                    Kami menyediakan layanan rental mobil terbaik dengan harga terjangkau dan armada berkualitas.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-4 text-blue-400">Layanan</h3>
                <ul class="text-sm space-y-2">
                    <li><a href="<?= USER_URL ?>mobil.php" class="text-gray-400 hover:text-white transition">Sewa Mobil</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Sewa dengan Sopir</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Sewa Jangka Panjang</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Antar Jemput</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-4 text-blue-400">Tautan</h3>
                <ul class="text-sm space-y-2">
                    <li><a href="<?= USER_URL ?>" class="text-gray-400 hover:text-white transition">Beranda</a></li>
                    <li><a href="<?= USER_URL ?>tentang.php" class="text-gray-400 hover:text-white transition">Tentang Kami</a></li>
                    <li><a href="<?= USER_URL ?>kontak.php" class="text-gray-400 hover:text-white transition">Kontak</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Syarat & Ketentuan</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-4 text-blue-400">Kontak Kami</h3>
                <ul class="text-sm space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt text-blue-400 mt-1 mr-2"></i>
                        <span class="text-gray-400">Jl. Contoh No. 123, Kota, Kode Pos 12345</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-phone text-blue-400 mt-1 mr-2"></i>
                        <span class="text-gray-400">+62 123 4567 890</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-envelope text-blue-400 mt-1 mr-2"></i>
                        <span class="text-gray-400">info@rentalmobil.com</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-700 mt-8 pt-6 text-sm text-gray-500 text-center">
            <p>&copy; <?= date('Y') ?> Rental Mobil. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    // Tunggu sampai dokumen benar-benar dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Handler untuk tombol pembayaran
        document.getElementById('pay-button').addEventListener('click', function() {
            // Tampilkan proses pembayaran
            document.getElementById('payment-options').classList.add('hidden');
            document.getElementById('payment-processing').classList.remove('hidden');
            
            // Buka Snap popup
            window.snap.pay('<?= $snapToken ?>', {
                onSuccess: function(result) {
                    // Kirim notifikasi ke server bahwa pembayaran berhasil
                    window.location.href = '<?= MIDTRANS_FINISH_URL ?>?order_id=<?= $kode_pemesanan ?>&midtrans_order_id=<?= $unique_order_id ?>&status=success';
                },
                onPending: function(result) {
                    // Kirim notifikasi ke server bahwa pembayaran pending
                    window.location.href = '<?= MIDTRANS_FINISH_URL ?>?order_id=<?= $kode_pemesanan ?>&midtrans_order_id=<?= $unique_order_id ?>&status=pending';
                },
                onError: function(result) {
                    // Kirim notifikasi ke server bahwa pembayaran error
                    window.location.href = '<?= MIDTRANS_ERROR_URL ?>?order_id=<?= $kode_pemesanan ?>&midtrans_order_id=<?= $unique_order_id ?>';
                },
                onClose: function() {
                    // Kembalikan tombol pembayaran saat popup ditutup tanpa menyelesaikan
                    document.getElementById('payment-options').classList.remove('hidden');
                    document.getElementById('payment-processing').classList.add('hidden');
                }
            });
        });
        
        // Toggle untuk dropdown menu
        const userDropdown = document.getElementById('userDropdown');
        const userMenu = document.getElementById('userMenu');
        
        if (userDropdown && userMenu) {
            userDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('show');
            });
            
            // Tutup dropdown saat klik di luar
            document.addEventListener('click', function(e) {
                if (userMenu.classList.contains('show') && !userMenu.contains(e.target) && e.target !== userDropdown) {
                    userMenu.classList.remove('show');
                }
            });
        }
    });
</script>
</body>
</html> 