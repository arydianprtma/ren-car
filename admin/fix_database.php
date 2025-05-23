<?php
/**
 * Script perbaikan database untuk status_pemesanan kosong
 */
// Gunakan path relatif yang benar untuk file-file yang dibutuhkan
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once 'includes/auth_check.php';

// Hanya admin yang boleh mengakses halaman ini
// Karena sudah ada auth_check.php, tidak perlu pengecekan tambahan
// Auth_check.php sudah memastikan bahwa pengguna adalah admin melalui isAdminLoggedIn()

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
    
    <!-- Tailwind CSS dan Font Awesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            background-image: radial-gradient(#e0f2fe 1px, transparent 1px);
            background-size: 25px 25px;
        }
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .btn {
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .icon-wrapper {
            border-radius: 50%;
            height: 70px;
            width: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-16 max-w-6xl">
        <!-- Header dengan efek gradient -->
        <div class="mb-10 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                <span class="bg-gradient-to-r from-blue-600 to-sky-400 bg-clip-text text-transparent">Database Maintenance</span>
            </h1>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Alat untuk memperbaiki database dan menormalkan status pemesanan yang tidak valid
            </p>
        </div>
        
        <!-- Action Bar -->
        <div class="flex justify-end mb-8">
            <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="flex items-center bg-white border border-gray-200 text-gray-700 py-2.5 px-5 rounded-lg shadow-sm hover:bg-gray-50 transition-all duration-200 btn">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Pemesanan
            </a>
        </div>
        
        <!-- Alert Messages -->
        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-5 mb-8 rounded-r-lg shadow-md animate-fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-red-800">Error</h3>
                        <div class="mt-2 text-red-700">
                            <p><?= $errorMessage ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($isSuccess): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-5 mb-8 rounded-r-lg shadow-md animate-fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-green-800">Perbaikan Berhasil!</h3>
                        <div class="mt-2 text-green-700">
                            <p>Total <span class="font-bold"><?= $totalFixed ?></span> data pemesanan berhasil diperbaiki.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Main Content Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
            <!-- Status Card -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md p-6 card">
                    <div class="flex items-center mb-6">
                        <div class="icon-wrapper">
                            <i class="fas fa-chart-pie text-white text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Status Database</h2>
                            <p class="text-sm text-gray-500">Kondisi saat ini dari data pemesanan</p>
                        </div>
                    </div>
                    
                    <?php if ($needsFixing > 0): ?>
                        <div class="bg-amber-50 border-l-4 border-amber-500 p-5 mb-6 rounded-lg">
                            <div class="flex items-start">
                                <div class="mr-4">
                                    <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-amber-800">Ditemukan <?= $needsFixing ?> data yang perlu diperbaiki</h3>
                                    <ul class="list-disc pl-5 mt-3 space-y-1 text-amber-700">
                                        <li>
                                            <span class="font-semibold"><?= $nullStatus ?></span> pemesanan dengan status kosong
                                            <?php if ($nullStatus > 0): ?>
                                                <span class="ml-2 bg-amber-200 text-amber-800 text-xs px-2 py-0.5 rounded-full">Akan diubah menjadi "menunggu"</span>
                                            <?php endif; ?>
                                        </li>
                                        <li>
                                            <span class="font-semibold"><?= $incorrectStatus ?></span> pemesanan dengan metode pembayaran tapi status masih 'menunggu'
                                            <?php if ($incorrectStatus > 0): ?>
                                                <span class="ml-2 bg-amber-200 text-amber-800 text-xs px-2 py-0.5 rounded-full">Akan diubah menjadi "dibayar"</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="" class="mt-6">
                            <p class="text-gray-600 mb-4">Klik tombol di bawah untuk memperbaiki semua masalah database yang terdeteksi:</p>
                            <button type="submit" name="fix_database" class="bg-gradient-to-r from-blue-500 to-sky-500 hover:from-blue-600 hover:to-sky-600 text-white px-6 py-3 rounded-xl shadow-lg transition-all flex items-center justify-center btn">
                                <i class="fas fa-tools mr-2"></i> Perbaiki Database Sekarang
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-lg flex items-center">
                            <div class="mr-4">
                                <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-emerald-800">Semua data pemesanan sudah benar!</h3>
                                <p class="text-emerald-700 mt-1">Tidak ada masalah status_pemesanan yang terdeteksi dalam database.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 card">
                    <div class="flex items-center mb-6">
                        <div class="icon-wrapper">
                            <i class="fas fa-info-circle text-white text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Informasi</h2>
                            <p class="text-sm text-gray-500">Tentang proses perbaikan</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <p class="font-medium text-blue-800 mb-1">Perbaikan Status Kosong</p>
                            <p class="text-sm text-blue-700">Mengubah semua status_pemesanan yang kosong (NULL atau string kosong) menjadi 'menunggu'</p>
                        </div>
                        
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <p class="font-medium text-blue-800 mb-1">Perbaikan Status Pembayaran</p>
                            <p class="text-sm text-blue-700">Mengubah status menjadi 'dibayar' untuk pemesanan yang sudah memiliki metode_pembayaran tetapi statusnya masih 'menunggu'</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-shield-alt mr-2 text-gray-400"></i>
                            <p>Semua perubahan database dilakukan dalam satu transaksi untuk memastikan integritas data.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Card: Database Statistics -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-10 card">
            <div class="flex items-center mb-6">
                <div class="icon-wrapper">
                    <i class="fas fa-database text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Diagram Status Database</h2>
                    <p class="text-sm text-gray-500">Visualisasi kondisi data dalam database</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="p-4 bg-blue-50 rounded-lg text-center">
                    <p class="text-sm text-blue-600 uppercase font-semibold mb-1">Status Menunggu</p>
                    <div class="flex justify-center">
                        <div class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-sky-400 bg-clip-text text-transparent"><?= $incorrectStatus ?></div>
                    </div>
                </div>
                
                <div class="p-4 bg-green-50 rounded-lg text-center">
                    <p class="text-sm text-green-600 uppercase font-semibold mb-1">Status Kosong</p>
                    <div class="flex justify-center">
                        <div class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-400 bg-clip-text text-transparent"><?= $nullStatus ?></div>
                    </div>
                </div>
                
                <div class="p-4 bg-purple-50 rounded-lg text-center">
                    <p class="text-sm text-purple-600 uppercase font-semibold mb-1">Total Perbaikan</p>
                    <div class="flex justify-center">
                        <div class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-400 bg-clip-text text-transparent"><?= $needsFixing ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($needsFixing > 0): ?>
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-gradient-to-r from-red-500 to-amber-500 h-2 rounded-full" style="width: 100%"></div>
                </div>
                <p class="text-center text-amber-600 font-medium">Database membutuhkan perbaikan</p>
            <?php else: ?>
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-2 rounded-full" style="width: 100%"></div>
                </div>
                <p class="text-center text-emerald-600 font-medium">Database dalam kondisi baik</p>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm">
            <p>&copy; <?= date('Y') ?> Rental Mobil Admin Panel</p>
            <p class="mt-1">Database Maintenance Tool</p>
        </div>
    </div>

    <script>
        // Animasi sederhana untuk card
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('translate-y-0', 'opacity-100');
                    card.classList.remove('translate-y-4', 'opacity-0');
                }, 100 * index);
            });
        });
    </script>
</body>
</html> 