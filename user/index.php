<?php
require_once 'includes/header.php';

// Ambil data mobil
$db = new Database();
$conn = $db->getConnection();

// Ambil mobil terbaru (termasuk yang sedang disewa)
$stmt = $conn->query("SELECT m.*, k.nama_kategori FROM mobil m 
                      LEFT JOIN kategori_mobil k ON m.kategori_id = k.id 
                      ORDER BY m.id DESC LIMIT 6");
$mobilTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil informasi pemesanan untuk mobil yang sedang disewa
$mobilDisewa = [];
$sql = "SELECT p.mobil_id, p.tanggal_selesai 
        FROM pemesanan p 
        WHERE p.status_pemesanan NOT IN ('dibatalkan', 'selesai') 
        AND p.tanggal_selesai >= CURRENT_DATE()";
$stmt = $conn->query($sql);
$pemesananAktif = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buat array untuk menyimpan tanggal pengembalian mobil
foreach ($pemesananAktif as $pemesanan) {
    $mobilDisewa[$pemesanan['mobil_id']] = $pemesanan['tanggal_selesai'];
}

// Ambil kategori mobil
$stmt = $conn->query("SELECT * FROM kategori_mobil ORDER BY nama_kategori ASC");
$kategoriMobil = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    
    /* Animasi floating untuk cards */
    @keyframes floating {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }
    
    .hover-float:hover {
        animation: floating 3s ease-in-out infinite;
    }
    
    /* Pola latar belakang */
    .pattern-grid-lg {
        background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
        background-size: 20px 20px;
    }
    
    /* Scale hover effect */
    .hover-scale {
        transition: transform 0.3s ease-in-out;
    }
    
    .hover-scale:hover {
        transform: scale(1.03);
    }
    
    /* Line clamp untuk teks panjang */
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    body, html {
        overflow-x: hidden;
    }
</style>

<!-- Hero Section -->
<section class="hero-section min-h-[60vh] flex items-center justify-center bg-gradient-to-r from-blue-700 to-blue-500 relative overflow-hidden px-2 sm:px-0 text-justify sm:text-center">
    <div class="absolute inset-0 bg-black opacity-40"></div>
    <div class="absolute inset-0 bg-blue-900 opacity-10 pattern-grid-lg"></div>
    <div class="absolute top-0 right-0 w-60 h-60 sm:w-96 sm:h-96 bg-blue-400 opacity-20 rounded-full -mt-10 sm:-mt-20 -mr-10 sm:-mr-20 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-40 h-40 sm:w-72 sm:h-72 bg-blue-300 opacity-20 rounded-full -mb-10 sm:-mb-20 -ml-10 sm:-ml-20 blur-3xl"></div>
    
    <div class="container mx-auto px-2 sm:px-6 text-justify sm:text-center text-white py-10 sm:py-16 relative z-10">
        <h1 class="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-bold mb-4 sm:mb-6 leading-tight drop-shadow-lg text-justify sm:text-center">Rental Mobil Terbaik untuk Perjalanan Anda</h1>
        <p class="text-base sm:text-lg md:text-xl mb-6 sm:mb-10 max-w-2xl sm:max-w-3xl mx-auto drop-shadow-md text-justify sm:text-center">Sewa mobil dengan harga terjangkau dan pelayanan terbaik</p>
        <div class="max-w-full sm:max-w-4xl mx-auto bg-white/10 backdrop-blur-sm p-1 sm:p-2 rounded-xl shadow-2xl border border-white/20 text-justify">
            <form action="mobil.php" method="GET" class="flex flex-col md:flex-row bg-white p-3 sm:p-5 rounded-lg text-justify">
                <div class="flex-1 mb-3 md:mb-0 md:mr-3">
                    <label for="kategori" class="block text-gray-700 text-xs sm:text-sm font-semibold mb-1 sm:mb-2">Kategori Mobil</label>
                    <select id="kategori" name="kategori" class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700 text-xs sm:text-base">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategoriMobil as $kategori): ?>
                        <option value="<?= $kategori['id'] ?>"><?= $kategori['nama_kategori'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1 mb-3 md:mb-0 md:mr-3">
                    <label for="tanggal_mulai" class="block text-gray-700 text-xs sm:text-sm font-semibold mb-1 sm:mb-2">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700 text-xs sm:text-base" min="<?= date('Y-m-d') ?>" placeholder="Pilih tanggal mulai">
                </div>
                <div class="flex-1 mb-3 md:mb-0">
                    <label for="tanggal_selesai" class="block text-gray-700 text-xs sm:text-sm font-semibold mb-1 sm:mb-2">Tanggal Selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="w-full px-3 py-2 sm:px-4 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-700 text-xs sm:text-base" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" placeholder="Pilih tanggal selesai">
                </div>
                <div class="flex items-end ml-0 md:ml-4 mt-2 md:mt-0">
                    <button type="submit" class="bg-blue-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all font-semibold w-full md:w-auto flex items-center justify-center text-sm sm:text-base">
                        <i class="fas fa-search mr-2"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Mobil Terbaru Section -->
<section class="py-10 sm:py-16 bg-white text-justify">
    <div class="container mx-auto px-2 sm:px-6 text-justify">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 sm:mb-10 gap-2 sm:gap-0 text-justify sm:text-left">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 relative text-justify sm:text-left">
                <span class="relative z-10">Mobil Terbaru</span>
                <span class="absolute -bottom-2 sm:-bottom-3 left-0 w-1/2 h-1 bg-blue-600"></span>
            </h2>
            <a href="mobil.php" class="text-blue-600 hover:text-blue-700 flex items-center font-medium group transition-all text-sm sm:text-base text-justify sm:text-left">
                Lihat Semua <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
        
        <!-- Skeleton Loader untuk Mobil Terbaru -->
        <div id="mobil-terbaru-skeleton" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl border border-gray-100">
                    <div class="h-56 bg-gray-200 relative overflow-hidden skeleton-shimmer">
                        <div class="absolute top-0 right-0 bg-gray-300 px-4 py-1 m-3 rounded-full w-24 h-6 skeleton-shimmer"></div>
                    </div>
                    <div class="p-6">
                        <div class="h-7 bg-gray-200 rounded-md w-3/4 mb-3 skeleton-shimmer"></div>
                        <div class="flex items-center text-gray-600 text-sm mb-4 space-x-4">
                            <div class="h-5 bg-gray-200 rounded-md w-16 skeleton-shimmer"></div>
                            <div class="h-5 bg-gray-200 rounded-md w-20 skeleton-shimmer"></div>
                            <div class="h-5 bg-gray-200 rounded-md w-16 skeleton-shimmer"></div>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <div class="h-6 bg-gray-200 rounded-md w-28 skeleton-shimmer"></div>
                            <div class="h-10 bg-gray-200 rounded-lg w-20 skeleton-shimmer"></div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        
        <!-- Konten Mobil Terbaru yang Sebenarnya (awalnya tersembunyi) -->
        <div id="mobil-terbaru-content" class="hidden">
            <?php if (empty($mobilTerbaru)): ?>
                <div class="flex flex-col items-center justify-center py-16 bg-gray-50 rounded-xl animate-fadeIn">
                    <div class="w-32 h-32 mb-6 text-gray-300">
                        <i class="fas fa-car-side text-8xl animate-pulse text-blue-200"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-700 mb-2">Belum Ada Mobil</h3>
                    <p class="text-gray-500 text-center max-w-md">Saat ini belum ada data mobil tersedia. Silakan periksa kembali nanti.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($mobilTerbaru as $mobil): ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl border border-gray-200 group">
                            <div class="h-56 bg-gray-200 relative overflow-hidden">
                                <?php if (!empty($mobil['foto_mobil'])): ?>
                                    <img src="<?= ASSETS_URL ?>uploads/mobil/<?= $mobil['foto_mobil'] ?>" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 <?= ($mobil['status'] != 'tersedia') ? 'opacity-70' : '' ?>">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-gray-100 p-4">
                                        <i class="fas fa-car-side text-5xl text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-500 text-center">Foto mobil tidak tersedia</p>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-0 right-0 bg-gradient-to-r from-blue-600 to-blue-500 text-white px-4 py-1 m-3 rounded-full text-xs font-medium shadow-md">
                                    <?= $mobil['nama_kategori'] ?? 'Uncategorized' ?>
                                </div>
                                
                                <?php 
                                // Tampilkan status mobil jika tidak tersedia
                                if ($mobil['status'] != 'tersedia'): ?>
                                    <div class="absolute top-0 left-0 bg-gradient-to-r from-yellow-600 to-yellow-500 text-white px-4 py-1 m-3 rounded-full text-xs font-medium shadow-md">
                                        <?php if ($mobil['status'] == 'disewa'): ?>
                                            <i class="fas fa-clock mr-1"></i> Sedang Disewa
                                        <?php elseif ($mobil['status'] == 'pemeliharaan'): ?>
                                            <i class="fas fa-tools mr-1"></i> Dalam Pemeliharaan
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (isset($mobilDisewa[$mobil['id']])): ?>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="bg-black bg-opacity-60 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-lg">
                                                <i class="fas fa-calendar-alt mr-1"></i> Tersedia kembali pada: <br>
                                                <span class="text-center block mt-1"><?= date('d F Y', strtotime($mobilDisewa[$mobil['id']])) ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors truncate"><?= $mobil['merk'] ?> <?= $mobil['model'] ?></h3>
                                <div class="flex flex-wrap items-center text-gray-600 text-sm mb-4 gap-3">
                                    <span class="flex items-center"><i class="fas fa-car mr-2 text-blue-500"></i> <?= $mobil['tahun_produksi'] ?></span>
                                    <span class="flex items-center"><i class="fas fa-user mr-2 text-blue-500"></i> <?= $mobil['kapasitas'] ?> Orang</span>
                                    <span class="flex items-center"><i class="fas fa-gear mr-2 text-blue-500"></i> <?= ucfirst($mobil['transmisi']) ?></span>
                                    <span class="flex items-center"><i class="fas fa-palette mr-2 text-blue-500"></i> <?= ucfirst($mobil['warna']) ?></span>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                    <div class="text-blue-600 font-bold text-lg">
                                        Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?> <span class="text-sm text-gray-500 font-normal">/ Hari</span>
                                    </div>
                                    <?php if ($mobil['status'] == 'tersedia'): ?>
                                        <a href="detail-mobil.php?id=<?= $mobil['id'] ?>" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition-all font-medium flex items-center">
                                            <i class="fas fa-info-circle mr-1"></i> Detail
                                        </a>
                                    <?php else: ?>
                                        <a href="detail-mobil.php?id=<?= $mobil['id'] ?>" class="bg-yellow-600 text-white px-5 py-2 rounded-lg hover:bg-yellow-700 transition-all font-medium flex items-center">
                                            <i class="fas fa-info-circle mr-1"></i> Lihat Detail
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Kategori Section -->
<section class="py-10 sm:py-16 bg-gray-50 text-justify">
    <div class="container mx-auto px-2 sm:px-6 text-justify">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-8 sm:mb-12 text-center relative text-justify sm:text-center">
            <span class="relative z-10">Kategori Mobil</span>
            <span class="absolute bottom-[-8px] sm:bottom-[-10px] left-1/2 transform -translate-x-1/2 w-16 sm:w-24 h-1 bg-blue-600"></span>
        </h2>
        
        <!-- Skeleton Loader untuk Kategori Mobil -->
        <div id="kategori-mobil-skeleton" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8 mt-6 sm:mt-10">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg border border-gray-200">
                    <div class="p-8">
                        <div class="h-7 bg-gray-200 rounded-md w-2/3 mb-3 skeleton-shimmer"></div>
                        <div class="h-4 bg-gray-200 rounded-md w-full mb-2 skeleton-shimmer"></div>
                        <div class="h-4 bg-gray-200 rounded-md w-full mb-2 skeleton-shimmer"></div>
                        <div class="h-4 bg-gray-200 rounded-md w-3/4 mb-5 skeleton-shimmer"></div>
                        <div class="h-5 bg-gray-200 rounded-md w-40 skeleton-shimmer"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        
        <!-- Konten Kategori Mobil yang Sebenarnya (awalnya tersembunyi) -->
        <div id="kategori-mobil-content" class="hidden">
            <?php if (empty($kategoriMobil)): ?>
                <div class="flex flex-col items-center justify-center py-16 bg-white rounded-xl shadow-sm animate-fadeIn">
                    <div class="w-32 h-32 mb-6 text-gray-300">
                        <i class="fas fa-list-alt text-8xl animate-pulse text-blue-200"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-700 mb-2">Belum Ada Kategori</h3>
                    <p class="text-gray-500 text-center max-w-md">Saat ini belum ada data kategori tersedia. Silakan periksa kembali nanti.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-10">
                    <?php foreach ($kategoriMobil as $kategori): ?>
                        <a href="mobil.php?kategori=<?= $kategori['id'] ?>" class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg border border-gray-200 group">
                            <div class="p-8 relative">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full -mt-10 -mr-10 opacity-50 group-hover:bg-blue-100 transition-colors"></div>
                                <div class="relative z-10">
                                    <div class="w-12 h-12 flex items-center justify-center bg-blue-100 text-blue-600 rounded-lg mb-4 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                        <i class="fas fa-car-side text-xl"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors"><?= $kategori['nama_kategori'] ?></h3>
                                    <p class="text-gray-600 mb-5 line-clamp-3"><?= $kategori['deskripsi'] ?></p>
                                    <div class="text-blue-600 group-hover:text-blue-700 flex items-center font-medium">
                                        Lihat Mobil <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Keunggulan Section -->
<section class="py-10 sm:py-16 bg-white text-justify">
    <div class="container mx-auto px-2 sm:px-6 text-justify">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-8 sm:mb-12 text-center relative text-justify sm:text-center">
            <span class="relative z-10">Mengapa Memilih Kami?</span>
            <span class="absolute bottom-[-8px] sm:bottom-[-10px] left-1/2 transform -translate-x-1/2 w-16 sm:w-24 h-1 bg-blue-600"></span>
        </h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
            <div class="text-center bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 group">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full p-5 inline-flex items-center justify-center w-20 h-20 mb-6 shadow-md group-hover:scale-110 transition-transform">
                    <i class="fas fa-car-side text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">Armada Berkualitas</h3>
                <p class="text-gray-600">Mobil terbaru, terawat, dan nyaman untuk berbagai kebutuhan Anda.</p>
            </div>
            
            <div class="text-center bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 group">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full p-5 inline-flex items-center justify-center w-20 h-20 mb-6 shadow-md group-hover:scale-110 transition-transform">
                    <i class="fas fa-money-bill-wave text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">Harga Terjangkau</h3>
                <p class="text-gray-600">Kami menawarkan harga kompetitif tanpa mengorbankan kualitas layanan.</p>
            </div>
            
            <div class="text-center bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 group">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full p-5 inline-flex items-center justify-center w-20 h-20 mb-6 shadow-md group-hover:scale-110 transition-transform">
                    <i class="fas fa-headset text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">Layanan 24/7</h3>
                <p class="text-gray-600">Tim customer service kami siap membantu Anda kapan saja.</p>
            </div>
            
            <div class="text-center bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 group">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full p-5 inline-flex items-center justify-center w-20 h-20 mb-6 shadow-md group-hover:scale-110 transition-transform">
                    <i class="fas fa-shield-alt text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">Keamanan Terjamin</h3>
                <p class="text-gray-600">Semua mobil telah melalui pemeriksaan keamanan rutin dan dilengkapi asuransi.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-14 sm:py-20 bg-gradient-to-r from-blue-700 to-blue-500 text-white relative overflow-hidden text-justify">
    <div class="absolute inset-0 bg-pattern opacity-10"></div>
    <div class="absolute top-0 right-0 w-40 h-40 sm:w-96 sm:h-96 bg-blue-400 opacity-20 rounded-full -mt-10 sm:-mt-20 -mr-10 sm:-mr-20 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-32 h-32 sm:w-72 sm:h-72 bg-blue-300 opacity-20 rounded-full -mb-10 sm:-mb-20 -ml-10 sm:-ml-20 blur-3xl"></div>
    <div class="container mx-auto px-2 sm:px-6 text-center relative z-10 text-justify sm:text-center">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-5 text-justify sm:text-center">Siap Untuk Menyewa Mobil?</h2>
        <p class="text-base sm:text-xl mb-6 sm:mb-10 max-w-xl sm:max-w-2xl mx-auto text-justify sm:text-center">Kami siap membantu perjalanan Anda dengan armada terbaik kami.</p>
        <a href="mobil.php" class="bg-white text-blue-600 px-6 sm:px-8 py-2 sm:py-3 rounded-lg font-semibold hover:bg-blue-50 transition-all inline-flex items-center justify-center hover:shadow-lg gap-2 text-sm sm:text-base">
            <i class="fas fa-car-side"></i> Sewa Sekarang
        </a>
    </div>
</section>

<!-- JavaScript untuk Skeleton Loader dan Interaksi -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menampilkan konten setelah halaman dimuat
    setTimeout(function() {
        // Sembunyikan skeleton loader dan tampilkan konten asli untuk Mobil Terbaru
        document.getElementById('mobil-terbaru-skeleton').style.display = 'none';
        document.getElementById('mobil-terbaru-content').classList.remove('hidden');
        document.getElementById('mobil-terbaru-content').classList.add('animate-fadeIn');
        
        // Sembunyikan skeleton loader dan tampilkan konten asli untuk Kategori Mobil
        document.getElementById('kategori-mobil-skeleton').style.display = 'none';
        document.getElementById('kategori-mobil-content').classList.remove('hidden');
        document.getElementById('kategori-mobil-content').classList.add('animate-fadeIn');
    }, 1000); // Delay 1 detik untuk menampilkan skeleton loader
    
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
    
    // Tambahkan efek hover pada card
    const cards = document.querySelectorAll('.group');
    cards.forEach(card => {
        card.classList.add('hover-scale');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 