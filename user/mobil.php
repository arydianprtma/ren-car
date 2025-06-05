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

// Buat query dasar - Tampilkan semua mobil, tidak hanya yang tersedia
$sql = "SELECT m.*, k.nama_kategori FROM mobil m 
        LEFT JOIN kategori_mobil k ON m.kategori_id = k.id";
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

// Mulai dengan WHERE jika belum ada kondisi
if (empty($kategori_id) && empty($kata_kunci)) {
    $sql .= " WHERE 1=1";
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

// Ambil informasi pemesanan aktif untuk mobil yang sedang disewa
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
    <div class="container mx-auto px-4">
        <form action="mobil.php" method="GET" class="bg-white rounded-xl shadow-lg p-4 md:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 md:gap-6">
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
                
                <div class="flex flex-col">
                    <label for="urutkan" class="block text-gray-700 text-sm font-medium mb-2">Urutkan</label>
                    <div class="flex items-center h-[40px] w-full">
                        <select id="urutkan" name="urutkan" class="w-full px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="terbaru" <?= ($urutkan == 'terbaru') ? 'selected' : '' ?>>Terbaru</option>
                            <option value="harga_terendah" <?= ($urutkan == 'harga_terendah') ? 'selected' : '' ?>>Harga Terendah</option>
                            <option value="harga_tertinggi" <?= ($urutkan == 'harga_tertinggi') ? 'selected' : '' ?>>Harga Tertinggi</option>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-4 sm:px-6 py-2 rounded-r-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tombol Filter untuk Mobile -->
            <div class="mt-4 md:hidden">
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                    <i class="fas fa-filter mr-2"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Mobil List -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <?php if (!empty($mobilList)): ?>
            <!-- Hasil Pencarian Mobil (awalnya tersembunyi) -->
            <div id="mobil-list-content" class="hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($mobilList as $mobil): ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl border border-gray-200 group hover-scale h-full flex flex-col">
                            <div class="h-48 sm:h-60 bg-gray-200 relative overflow-hidden">
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
                                
                                <?php 
                                // Tampilkan badge fitur unggulan jika ada fitur mobil
                                if (!empty($mobil['fitur'])) {
                                    $fiturJson = json_decode($mobil['fitur'], true);
                                    if (!empty($fiturJson)) {
                                        $fiturMapping = [
                                            'ac' => ['icon' => 'snowflake', 'label' => 'AC'],
                                            'power_steering' => ['icon' => 'dharmachakra', 'label' => 'Power Steering'],
                                            'power_window' => ['icon' => 'window-maximize', 'label' => 'Power Window'],
                                            'central_lock' => ['icon' => 'lock', 'label' => 'Central Lock'],
                                            'audio_system' => ['icon' => 'music', 'label' => 'Audio System'],
                                            'airbag' => ['icon' => 'car-burst', 'label' => 'Airbag'],
                                            'seatbelt' => ['icon' => 'user-shield', 'label' => 'Seat Belt'],
                                            'pewangi' => ['icon' => 'spray-can-sparkles', 'label' => 'Pewangi'],
                                            'bluetooth' => ['icon' => 'bluetooth', 'label' => 'Bluetooth'],
                                            'cruise_control' => ['icon' => 'tachometer-alt', 'label' => 'Cruise Control'],
                                            'parking_sensor' => ['icon' => 'parking', 'label' => 'Parking Sensor'],
                                            'backup_camera' => ['icon' => 'camera', 'label' => 'Backup Camera'],
                                            'child_lock' => ['icon' => 'child', 'label' => 'Child Lock'],
                                            'fog_lamp' => ['icon' => 'lightbulb', 'label' => 'Fog Lamp'],
                                            'kursi_bayi' => ['icon' => 'baby', 'label' => 'Kursi Bayi']
                                        ];
                                        
                                        // Ambil hingga 3 fitur untuk ditampilkan di badge
                                        $fiturToShow = [];
                                        $counter = 0;
                                        
                                        foreach ($fiturJson as $fiturKey) {
                                            if (isset($fiturMapping[$fiturKey]) && $counter < 3) {
                                                $fiturToShow[] = '<i class="fas fa-' . $fiturMapping[$fiturKey]['icon'] . ' mr-1"></i>' . $fiturMapping[$fiturKey]['label'];
                                                $counter++;
                                            }
                                        }
                                        
                                        if (!empty($fiturToShow)) {
                                            echo '<div class="absolute bottom-0 left-0 bg-gradient-to-r from-blue-600 to-blue-400 text-white px-3 py-1 m-3 rounded-full text-xs font-medium shadow-md">';
                                            echo implode(' · ', $fiturToShow);
                                            if (count($fiturJson) > 3) {
                                                echo ' <span class="bg-white/30 rounded-full px-1.5 py-0.5 text-[10px] ml-1">+' . (count($fiturJson) - 3) . '</span>';
                                            }
                                            echo '</div>';
                                        }
                                    }
                                }
                                ?>
                            </div>
                            <div class="p-4 sm:p-6 flex-1 flex flex-col">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2 sm:mb-3 group-hover:text-blue-600 transition-colors"><?= $mobil['merk'] ?> <?= $mobil['model'] ?></h3>
                                
                                <div class="flex flex-wrap items-center text-gray-600 text-xs sm:text-sm mb-3 sm:mb-4 gap-2 sm:gap-3">
                                    <span class="inline-flex items-center"><i class="fas fa-car mr-1 sm:mr-2 text-blue-500"></i> <?= $mobil['tahun_produksi'] ?></span>
                                    <span class="inline-flex items-center"><i class="fas fa-user mr-1 sm:mr-2 text-blue-500"></i> <?= $mobil['kapasitas'] ?> Orang</span>
                                    <span class="inline-flex items-center"><i class="fas fa-gear mr-1 sm:mr-2 text-blue-500"></i> <?= ucfirst($mobil['transmisi']) ?></span>
                                    <span class="inline-flex items-center"><i class="fas fa-gas-pump mr-1 sm:mr-2 text-blue-500"></i> <?= ucfirst($mobil['bahan_bakar']) ?></span>
                                </div>
                                
                                <?php if (!empty($mobil['fitur'])): ?>
                                    <div class="mb-3 sm:mb-4">
                                        <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Fitur Tersedia:</h4>
                                        <div class="flex flex-wrap gap-1 sm:gap-2">
                                            <?php 
                                            $fiturJson = json_decode($mobil['fitur'], true);
                                            $fiturMapping = [
                                                'ac' => ['icon' => 'snowflake', 'label' => 'AC'],
                                                'power_steering' => ['icon' => 'dharmachakra', 'label' => 'Power Steering'],
                                                'power_window' => ['icon' => 'window-maximize', 'label' => 'Power Window'],
                                                'central_lock' => ['icon' => 'lock', 'label' => 'Central Lock'],
                                                'audio_system' => ['icon' => 'music', 'label' => 'Audio System'],
                                                'airbag' => ['icon' => 'car-burst', 'label' => 'Airbag'],
                                                'seatbelt' => ['icon' => 'user-shield', 'label' => 'Seat Belt'],
                                                'pewangi' => ['icon' => 'spray-can-sparkles', 'label' => 'Pewangi'],
                                                'bluetooth' => ['icon' => 'bluetooth', 'label' => 'Bluetooth'],
                                                'cruise_control' => ['icon' => 'tachometer-alt', 'label' => 'Cruise Control'],
                                                'parking_sensor' => ['icon' => 'parking', 'label' => 'Parking Sensor'],
                                                'backup_camera' => ['icon' => 'camera', 'label' => 'Backup Camera'],
                                                'child_lock' => ['icon' => 'child', 'label' => 'Child Lock'],
                                                'fog_lamp' => ['icon' => 'lightbulb', 'label' => 'Fog Lamp'],
                                                'kursi_bayi' => ['icon' => 'baby', 'label' => 'Kursi Bayi']
                                            ];
                                            
                                            $counter = 0;
                                            if (!empty($fiturJson)) {
                                                foreach ($fiturJson as $fiturKey) {
                                                    if ($counter >= 5) {
                                                        echo '<span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full hover:bg-blue-200 transition-colors cursor-pointer" title="Klik untuk melihat detail" onclick="window.location.href=\'detail-mobil.php?id=' . $mobil['id'] . '\'">';
                                                        echo '<i class="fas fa-plus-circle mr-1"></i>' . (count($fiturJson) - 5) . ' lainnya';
                                                        echo '</span>';
                                                        break;
                                                    }
                                                    
                                                    if (isset($fiturMapping[$fiturKey])) {
                                                        echo '<span class="bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full hover:bg-blue-100 transition-colors">';
                                                        echo '<i class="fas fa-' . $fiturMapping[$fiturKey]['icon'] . ' mr-1"></i> ';
                                                        echo $fiturMapping[$fiturKey]['label'];
                                                        echo '</span>';
                                                        $counter++;
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-auto pt-2 border-t border-gray-100 flex justify-between items-center">
                                    <div class="text-blue-600 font-bold text-base sm:text-lg">
                                        Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?> <span class="text-xs sm:text-sm text-gray-500 font-normal">/ Hari</span>
                                    </div>
                                    
                                    <div>
                                        <?php if ($mobil['status'] == 'tersedia'): ?>
                                            <a href="detail-mobil.php?id=<?= $mobil['id'] ?><?= (!empty($tanggal_mulai) && !empty($tanggal_selesai)) ? '&tanggal_mulai=' . $tanggal_mulai . '&tanggal_selesai=' . $tanggal_selesai : '' ?>" class="bg-blue-600 text-white px-3 sm:px-4 py-1.5 sm:py-2 text-sm rounded-lg hover:bg-blue-700 transition-all font-medium flex items-center justify-center">
                                                <i class="fas fa-info-circle mr-1"></i> Detail
                                            </a>
                                        <?php else: ?>
                                            <a href="detail-mobil.php?id=<?= $mobil['id'] ?>" class="bg-gray-500 text-white px-3 sm:px-4 py-1.5 sm:py-2 text-sm rounded-lg hover:bg-gray-600 transition-all font-medium flex items-center justify-center">
                                                <i class="fas fa-info-circle mr-1"></i> Detail
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Skeleton Loader -->
            <div id="skeleton-loader-results" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php for ($i = 0; $i < min(count($mobilList), 6); $i++): ?>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                        <div class="h-48 sm:h-60 bg-gray-200 relative overflow-hidden skeleton-shimmer"></div>
                        <div class="p-4 sm:p-6">
                            <div class="h-6 bg-gray-200 rounded-md w-3/4 mb-3 skeleton-shimmer"></div>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <div class="h-5 bg-gray-200 rounded-md w-16 skeleton-shimmer"></div>
                                <div class="h-5 bg-gray-200 rounded-md w-20 skeleton-shimmer"></div>
                                <div class="h-5 bg-gray-200 rounded-md w-16 skeleton-shimmer"></div>
                            </div>
                            <div class="h-4 bg-gray-200 rounded-md w-full mb-2 skeleton-shimmer"></div>
                            <div class="h-4 bg-gray-200 rounded-md w-5/6 mb-2 skeleton-shimmer"></div>
                            <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                                <div class="h-6 bg-gray-200 rounded-md w-28 skeleton-shimmer"></div>
                                <div class="h-8 bg-gray-200 rounded-lg w-20 skeleton-shimmer"></div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        <?php else: ?>
            <!-- Pesan Mobil Tidak Ditemukan -->
            <div class="flex flex-col items-center justify-center py-12 sm:py-16 bg-white rounded-xl shadow-sm animate-fadeIn mb-6 sm:mb-12 px-4">
                <div class="w-24 sm:w-32 h-24 sm:h-32 mb-4 sm:mb-6 text-gray-300">
                    <i class="fas fa-car-side text-7xl sm:text-8xl animate-pulse text-blue-200"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-medium text-gray-700 mb-2">Mobil Tidak Ditemukan</h3>
                <p class="text-gray-500 text-center max-w-md mb-4 sm:mb-6">Tidak ada mobil yang sesuai dengan kriteria pencarian Anda.</p>
                <a href="mobil.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 sm:px-6 rounded-lg transition-all flex items-center text-sm sm:text-base">
                    <i class="fas fa-sync-alt mr-2"></i> Reset Pencarian
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menampilkan konten setelah halaman dimuat
    setTimeout(function() {
        // Sembunyikan skeleton loader dan tampilkan konten asli
        const skeletonLoader = document.getElementById('skeleton-loader-results');
        if (skeletonLoader) {
            skeletonLoader.style.display = 'none';
        }
        
        // Tampilkan konten asli jika ada
        const mobilListContent = document.getElementById('mobil-list-content');
        if (mobilListContent) {
            mobilListContent.classList.remove('hidden');
            mobilListContent.classList.add('animate-fadeIn');
        }
    }, 1000); // Delay 1 detik untuk menampilkan skeleton loader
    
    // Handle tanggal mulai-selesai
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');
    
    if (tanggalMulai && tanggalSelesai) {
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
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 