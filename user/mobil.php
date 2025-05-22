<?php
require_once 'includes/header.php';

// Ambil parameter filter
$kategori_id = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';
$kata_kunci = isset($_GET['kata_kunci']) ? $_GET['kata_kunci'] : '';
$urutkan = isset($_GET['urutkan']) ? $_GET['urutkan'] : 'terbaru';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil kategori mobil untuk filter
$stmt = $conn->query("SELECT * FROM kategori_mobil ORDER BY nama_kategori ASC");
$kategoriMobil = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buat query dasar
$sql = "SELECT m.*, k.nama_kategori FROM mobil m 
        LEFT JOIN kategori_mobil k ON m.kategori_id = k.id 
        WHERE m.status = 'tersedia'";
$params = [];

// Tambahkan filter ke query
if (!empty($kategori_id)) {
    $sql .= " AND m.kategori_id = :kategori_id";
    $params[':kategori_id'] = $kategori_id;
}

if (!empty($kata_kunci)) {
    $sql .= " AND (m.merk LIKE :kata_kunci OR m.model LIKE :kata_kunci)";
    $params[':kata_kunci'] = "%$kata_kunci%";
}

// Urutkan hasil
switch ($urutkan) {
    case 'harga_terendah':
        $sql .= " ORDER BY m.harga_sewa_per_hari ASC";
        break;
    case 'harga_tertinggi':
        $sql .= " ORDER BY m.harga_sewa_per_hari DESC";
        break;
    case 'terbaru':
    default:
        $sql .= " ORDER BY m.id DESC";
        break;
}

// Siapkan dan jalankan query
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$mobilList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Skeleton loader dengan efek shimmer */
    .skeleton-shimmer {
        position: relative;
        overflow: hidden;
        background: #f0f0f0;
    }
    
    .skeleton-shimmer::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        transform: translateX(-100%);
        background-image: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0) 0,
            rgba(255, 255, 255, 0.2) 20%,
            rgba(255, 255, 255, 0.5) 60%,
            rgba(255, 255, 255, 0)
        );
        animation: shimmer 2s infinite;
        content: '';
    }
    
    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }
    
    /* Animasi fadeIn untuk elemen kosong */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fadeIn {
        animation: fadeIn 0.5s ease-out forwards;
    }
    
    /* Animasi pulse untuk ikon */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .animate-pulse {
        animation: pulse 2s infinite;
    }
</style>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-700 to-blue-500 py-12 relative">
    <div class="absolute inset-0 bg-black opacity-30"></div>
    <div class="container mx-auto px-6 relative z-10">
        <div class="text-center text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">Armada Mobil Kami</h1>
            <p class="text-xl max-w-3xl mx-auto">Temukan mobil yang sesuai dengan kebutuhan perjalanan Anda</p>
        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="bg-white py-8 shadow-md relative -mt-8 rounded-t-3xl">
    <div class="container mx-auto px-6">
        <form action="mobil.php" method="GET" class="bg-white rounded-xl shadow-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <div>
                    <label for="kategori" class="block text-gray-700 text-sm font-medium mb-2">Kategori</label>
                    <select id="kategori" name="kategori" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategoriMobil as $kategori): ?>
                        <option value="<?= $kategori['id'] ?>" <?= ($kategori_id == $kategori['id']) ? 'selected' : '' ?>><?= $kategori['nama_kategori'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="kata_kunci" class="block text-gray-700 text-sm font-medium mb-2">Kata Kunci</label>
                    <input type="text" id="kata_kunci" name="kata_kunci" value="<?= htmlspecialchars($kata_kunci) ?>" placeholder="Merk, model..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
                
                <div>
                    <label for="tanggal_mulai" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?= $tanggal_mulai ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" min="<?= date('Y-m-d') ?>">
                </div>
                
                <div>
                    <label for="tanggal_selesai" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="<?= $tanggal_selesai ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                
                <div>
                    <label for="urutkan" class="block text-gray-700 text-sm font-medium mb-2">Urutkan</label>
                    <div class="flex items-end h-[40px]">
                        <select id="urutkan" name="urutkan" class="w-full px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="terbaru" <?= ($urutkan == 'terbaru') ? 'selected' : '' ?>>Terbaru</option>
                            <option value="harga_terendah" <?= ($urutkan == 'harga_terendah') ? 'selected' : '' ?>>Harga Terendah</option>
                            <option value="harga_tertinggi" <?= ($urutkan == 'harga_tertinggi') ? 'selected' : '' ?>>Harga Tertinggi</option>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-r-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Mobil List -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-6">
        <!-- Skeleton Loader (akan disembunyikan saat data dimuat) -->
        <div id="skeleton-loader" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (isset($_GET['kategori']) || isset($_GET['kata_kunci']) || isset($_GET['tanggal_mulai']) || isset($_GET['tanggal_selesai'])): ?>
                <!-- Skeleton untuk "Tidak ada mobil" saat filter aktif -->
                <div class="col-span-full text-center py-16 bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="flex flex-col items-center justify-center">
                        <div class="relative w-40 h-40 mb-6 skeleton-shimmer rounded-full"></div>
                        <div class="h-7 bg-gray-200 rounded-md w-64 mb-3 skeleton-shimmer"></div>
                        <div class="h-5 bg-gray-200 rounded-md w-80 mb-6 skeleton-shimmer"></div>
                        <div class="h-10 bg-gray-200 rounded-lg w-32 skeleton-shimmer"></div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Skeleton untuk daftar mobil saat tidak ada filter -->
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                        <!-- Skeleton untuk gambar -->
                        <div class="h-56 bg-gray-200 relative overflow-hidden skeleton-shimmer">
                            <div class="absolute top-0 right-0 bg-gray-300 px-4 py-1 m-3 rounded-full w-24 h-6 skeleton-shimmer"></div>
                        </div>
                        <div class="p-6">
                            <!-- Skeleton untuk judul -->
                            <div class="h-7 bg-gray-200 rounded-md w-3/4 mb-3 skeleton-shimmer"></div>
                            <!-- Skeleton untuk teks informasi -->
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="h-5 bg-gray-200 rounded-md w-16 skeleton-shimmer"></div>
                                <div class="h-5 bg-gray-200 rounded-md w-20 skeleton-shimmer"></div>
                                <div class="h-5 bg-gray-200 rounded-md w-16 skeleton-shimmer"></div>
                            </div>
                            <!-- Skeleton untuk footer card -->
                            <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                <div class="h-7 bg-gray-200 rounded-md w-28 skeleton-shimmer"></div>
                                <div class="h-9 bg-gray-200 rounded-lg w-20 skeleton-shimmer"></div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>

        <!-- Konten asli (akan ditampilkan setelah data dimuat) -->
        <div id="mobil-content" class="hidden">
            <?php if (empty($mobilList)): ?>
                <div class="text-center py-16 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200">
                    <div class="flex flex-col items-center justify-center">
                        <!-- Ilustrasi kosong yang lebih modern -->
                        <div class="relative w-52 h-52 mb-6 flex items-center justify-center empty-illustration">
                            <div class="absolute inset-0 bg-blue-50 rounded-full opacity-40"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-24 h-24 flex items-center justify-center rounded-full bg-blue-100 text-blue-500">
                                    <i class="fas fa-car-side text-5xl animate-pulse"></i>
                                </div>
                            </div>
                            <!-- Elemen dekoratif -->
                            <div class="absolute -top-2 -right-2 w-10 h-10 bg-gray-100 rounded-full opacity-60"></div>
                            <div class="absolute -bottom-3 -left-3 w-12 h-12 bg-blue-200 rounded-full opacity-60"></div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">Tidak ada mobil yang tersedia</h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">Tidak ada mobil yang sesuai dengan kriteria filter Anda. Silakan coba dengan kriteria pencarian lain.</p>
                        <a href="mobil.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-all inline-block shadow-md hover:shadow-lg">
                            <i class="fas fa-sync-alt mr-2"></i> Reset Filter
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Mobil Tersedia (<?= count($mobilList) ?>)</h2>
                    <?php if (!empty($kategori_id) || !empty($kata_kunci) || !empty($tanggal_mulai) || !empty($tanggal_selesai)): ?>
                        <a href="mobil.php" class="text-blue-600 hover:text-blue-700 flex items-center font-medium">
                            <i class="fas fa-times-circle mr-2"></i> Reset Filter
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($mobilList as $mobil): ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl border border-gray-100 hover:border-blue-100 group">
                            <div class="h-56 bg-gray-200 relative overflow-hidden">
                                <?php if (!empty($mobil['foto_mobil']) && file_exists('../assets/uploads/mobil/' . $mobil['foto_mobil'])): ?>
                                    <img src="<?= ASSETS_URL ?>uploads/mobil/<?= $mobil['foto_mobil'] ?>" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <img src="<?= ASSETS_URL ?>images/car-login.jpg" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php endif; ?>
                                <div class="absolute top-0 right-0 bg-gradient-to-r from-blue-600 to-blue-500 text-white px-4 py-1 m-3 rounded-full text-xs font-medium shadow-md">
                                    <?= $mobil['nama_kategori'] ?? 'Uncategorized' ?>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors"><?= $mobil['merk'] ?> <?= $mobil['model'] ?></h3>
                                <div class="flex items-center text-gray-600 text-sm mb-4 space-x-4">
                                    <span class="flex items-center"><i class="fas fa-car mr-2 text-blue-500"></i> <?= $mobil['tahun_produksi'] ?></span>
                                    <span class="flex items-center"><i class="fas fa-user mr-2 text-blue-500"></i> <?= $mobil['kapasitas'] ?> Orang</span>
                                    <span class="flex items-center"><i class="fas fa-gear mr-2 text-blue-500"></i> <?= ucfirst($mobil['transmisi']) ?></span>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                    <div class="text-blue-600 font-bold text-lg">
                                        Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?> <span class="text-sm text-gray-500 font-normal">/ Hari</span>
                                    </div>
                                    <a href="detail-mobil.php?id=<?= $mobil['id'] ?>" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition-all font-medium">Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-6">
        <div class="bg-gradient-to-r from-blue-700 to-blue-500 rounded-xl p-10 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full -mt-20 -mr-20"></div>
            <div class="absolute bottom-0 left-0 w-40 h-40 bg-white opacity-10 rounded-full -mb-10 -ml-10"></div>
            <div class="relative z-10 text-center text-white">
                <h2 class="text-3xl font-bold mb-4">Belum menemukan yang Anda cari?</h2>
                <p class="text-xl opacity-90 mb-8 max-w-3xl mx-auto">Hubungi kami untuk mendapatkan bantuan dalam memilih kendaraan yang sesuai dengan kebutuhan Anda.</p>
                <a href="<?= USER_URL ?>kontak.php" class="bg-white text-blue-600 font-semibold py-3 px-8 rounded-lg hover:bg-blue-50 transition duration-300 inline-block shadow-md">Hubungi Kami</a>
            </div>
        </div>
    </div>
</section>

<!-- Tambahkan script untuk menampilkan konten setelah beberapa saat -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan konten asli setelah simulasi loading
    setTimeout(function() {
        document.getElementById('skeleton-loader').classList.add('hidden');
        document.getElementById('mobil-content').classList.remove('hidden');
        
        // Tambahkan efek fade-in untuk elemen ilustrasi kosong
        const emptyIllustrasi = document.querySelector('.empty-illustration');
        if (emptyIllustrasi) {
            emptyIllustrasi.classList.add('animate-fadeIn');
        }
    }, 1500); // 1.5 detik simulasi loading
    
    // Handle tanggal mulai-selesai
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');
    
    tanggalMulai.addEventListener('change', function() {
        // Set minimum tanggal selesai = tanggal mulai + 1 hari
        if (tanggalMulai.value) {
            const nextDay = new Date(tanggalMulai.value);
            nextDay.setDate(nextDay.getDate() + 1);
            const formattedDate = nextDay.toISOString().split('T')[0];
            tanggalSelesai.min = formattedDate;
            
            // Reset tanggal selesai jika lebih awal dari tanggal mulai
            if (tanggalSelesai.value && new Date(tanggalSelesai.value) <= new Date(tanggalMulai.value)) {
                tanggalSelesai.value = formattedDate;
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 