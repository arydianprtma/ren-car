<?php
/**
 * Admin Dashboard
 */
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Mengambil data statistik
$db = new Database();
$conn = $db->getConnection();

// Total Mobil
$stmt = $conn->query("SELECT COUNT(*) AS total FROM mobil");
$totalMobil = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total User
$stmt = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUser = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total Pemesanan
$stmt = $conn->query("SELECT COUNT(*) AS total FROM pemesanan");
$totalPemesanan = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Pendapatan Total
$stmt = $conn->query("SELECT SUM(total_harga) AS total FROM pemesanan WHERE status_pemesanan IN ('dikonfirmasi', 'berjalan', 'selesai')");
$totalPendapatan = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Mobil yang sedang disewa
$stmt = $conn->query("SELECT COUNT(*) AS total FROM mobil WHERE status = 'disewa'");
$totalDisewa = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Pemesanan Terbaru
$stmt = $conn->query("
    SELECT p.*, u.nama as user_nama, m.merk, m.model 
    FROM pemesanan p
    JOIN users u ON p.user_id = u.id
    JOIN mobil m ON p.mobil_id = m.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$pesananTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mobil Populer
$stmt = $conn->query("
    SELECT m.*, COUNT(p.id) as jumlah_sewa
    FROM mobil m
    LEFT JOIN pemesanan p ON m.id = p.mobil_id
    GROUP BY m.id
    ORDER BY jumlah_sewa DESC
    LIMIT 5
");
$mobilPopuler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data untuk grafik pemesanan per bulan (12 bulan terakhir)
$stmt = $conn->query("
    SELECT 
        MONTH(created_at) as bulan, 
        YEAR(created_at) as tahun,
        COUNT(*) as jumlah,
        SUM(total_harga) as pendapatan
    FROM pemesanan
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY tahun, bulan
");
$dataBulan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format data untuk grafik
$bulanIndonesia = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$labels = [];
$dataPemesanan = [];
$dataPendapatan = [];

// Inisialisasi data untuk 12 bulan
for ($i = 11; $i >= 0; $i--) {
    $bulan = date('n', strtotime("-$i month"));
    $tahun = date('Y', strtotime("-$i month"));
    $labels[] = $bulanIndonesia[$bulan - 1] . ' ' . $tahun;
    $dataPemesanan[$bulan . '-' . $tahun] = 0;
    $dataPendapatan[$bulan . '-' . $tahun] = 0;
}

// Isi data dari database
foreach ($dataBulan as $data) {
    $key = $data['bulan'] . '-' . $data['tahun'];
    if (isset($dataPemesanan[$key])) {
        $dataPemesanan[$key] = (int)$data['jumlah'];
        $dataPendapatan[$key] = (int)$data['pendapatan'];
    }
}

// Konversi ke array untuk grafik
$chartDataPemesanan = array_values($dataPemesanan);
$chartDataPendapatan = array_values($dataPendapatan);
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-sm text-gray-600">Ringkasan performa sistem Rental Mobil</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 stats-card border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 rounded-full bg-blue-100 text-blue-600 mr-3 sm:mr-4">
                <i class="fas fa-car text-lg sm:text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs sm:text-sm">Total Mobil</p>
                <p class="text-xl sm:text-2xl font-bold"><?= $totalMobil ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 stats-card border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 rounded-full bg-green-100 text-green-600 mr-3 sm:mr-4">
                <i class="fas fa-users text-lg sm:text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs sm:text-sm">Total User</p>
                <p class="text-xl sm:text-2xl font-bold"><?= $totalUser ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 stats-card border-l-4 border-amber-500">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 rounded-full bg-amber-100 text-amber-600 mr-3 sm:mr-4">
                <i class="fas fa-clipboard-list text-lg sm:text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs sm:text-sm">Total Pesanan</p>
                <p class="text-xl sm:text-2xl font-bold"><?= $totalPemesanan ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 stats-card border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 rounded-full bg-purple-100 text-purple-600 mr-3 sm:mr-4">
                <i class="fas fa-money-bill-wave text-lg sm:text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs sm:text-sm">Pendapatan</p>
                <p class="text-lg sm:text-xl font-bold">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 stats-card border-l-4 border-cyan-500 sm:col-span-2 lg:col-span-1">
        <div class="flex items-center">
            <div class="p-2 sm:p-3 rounded-full bg-cyan-100 text-cyan-600 mr-3 sm:mr-4">
                <i class="fas fa-car-side text-lg sm:text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs sm:text-sm">Sedang Disewa</p>
                <p class="text-xl sm:text-2xl font-bold"><?= $totalDisewa ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Grafik dan Overview -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
    <!-- Grafik Pemesanan -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 lg:col-span-2">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-2 sm:mb-0">Statistik Pemesanan</h2>
            <div class="text-gray-500 text-sm">
                <i class="fas fa-sync-alt mr-1"></i> Update otomatis
            </div>
        </div>
        <div class="h-64 sm:h-80">
            <div id="chartPemesanan"></div>
        </div>
    </div>
    
    <!-- Status Overview -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Status Pemesanan</h2>
        
        <?php
        // Ambil jumlah pemesanan berdasarkan status
        $stmt = $conn->query("
            SELECT status_pemesanan, COUNT(*) as jumlah
            FROM pemesanan
            GROUP BY status_pemesanan
        ");
        $statusPemesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Definisikan warna untuk status
        $warna = [
            'menunggu' => 'amber',
            'dikonfirmasi' => 'blue',
            'berjalan' => 'green',
            'selesai' => 'gray',
            'dibatalkan' => 'red'
        ];
        
        // Hitung total untuk persentase
        $totalStatusPemesanan = array_sum(array_column($statusPemesanan, 'jumlah'));
        ?>
        
        <div class="space-y-3 sm:space-y-4">
            <?php foreach ($statusPemesanan as $status): ?>
            <?php 
                $persen = $totalStatusPemesanan > 0 ? round(($status['jumlah'] / $totalStatusPemesanan) * 100) : 0;
                $warnaStat = $warna[$status['status_pemesanan']] ?? 'gray';
            ?>
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700"><?= ucfirst($status['status_pemesanan']) ?></span>
                    <span class="text-sm font-medium text-gray-700"><?= $status['jumlah'] ?> (<?= $persen ?>%)</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-<?= $warnaStat ?>-500 h-2.5 rounded-full" style="width: <?= $persen ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-6">
            <h3 class="text-md font-semibold text-gray-800 mb-2">Distribusi Kategori Mobil</h3>
            
            <?php
            // Ambil jumlah mobil berdasarkan kategori
            $stmt = $conn->query("
                SELECT k.nama_kategori as nama, COUNT(m.id) as jumlah
                FROM kategori_mobil k
                LEFT JOIN mobil m ON k.id = m.kategori_id
                GROUP BY k.id
            ");
            $kategoriMobil = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Hitung total untuk persentase
            $totalKategoriMobil = array_sum(array_column($kategoriMobil, 'jumlah'));
            ?>
            
            <div id="chartKategori" class="h-32 sm:h-40"></div>
        </div>
    </div>
</div>

<!-- Tabel Data -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6 mb-6">
    <!-- Pemesanan Terbaru -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Pemesanan Terbaru</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-2 sm:py-3 px-2 sm:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="py-2 sm:py-3 px-2 sm:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Pelanggan</th>
                        <th class="py-2 sm:py-3 px-2 sm:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mobil</th>
                        <th class="py-2 sm:py-3 px-2 sm:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-2 sm:py-3 px-2 sm:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($pesananTerbaru)): ?>
                    <tr>
                        <td colspan="5" class="py-4 px-4 text-center text-gray-500 text-sm">Belum ada pemesanan</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($pesananTerbaru as $pesanan): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 sm:py-3 px-2 sm:px-4 text-sm">#<?= $pesanan['id'] ?></td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4 text-sm hidden sm:table-cell"><?= $pesanan['user_nama'] ?></td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4 text-sm">
                                <div class="truncate max-w-[120px] sm:max-w-none">
                                    <?= $pesanan['merk'] . ' ' . $pesanan['model'] ?>
                                </div>
                                <div class="text-xs text-gray-500 sm:hidden"><?= $pesanan['user_nama'] ?></div>
                            </td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4">
                                <?php
                                switch ($pesanan['status_pemesanan']) {
                                    case 'menunggu': 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-800">Menunggu</span>'; 
                                        break;
                                    case 'dikonfirmasi': 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">Dikonfirmasi</span>'; 
                                        break;
                                    case 'berjalan': 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Berjalan</span>'; 
                                        break;
                                    case 'selesai': 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">Selesai</span>'; 
                                        break;
                                    case 'dibatalkan': 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">Dibatalkan</span>'; 
                                        break;
                                    default: 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">Unknown</span>';
                                }
                                ?>
                            </td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4 text-gray-500 text-sm hidden sm:table-cell"><?= date('d/m/Y', strtotime($pesanan['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="py-3 px-4 sm:px-6 border-t border-gray-100 bg-gray-50">
            <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="text-primary-600 hover:text-primary-800 text-sm font-medium flex items-center">
                <span>Lihat semua pemesanan</span>
                <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
    
    <!-- Mobil Populer -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Mobil Populer</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mobil</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Sewa</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($mobilPopuler)): ?>
                    <tr>
                        <td colspan="4" class="py-4 px-4 text-center text-gray-500">Belum ada data mobil</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($mobilPopuler as $mobil): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-car text-gray-500"></i>
                                    </div>
                                    <span><?= $mobil['merk'] . ' ' . $mobil['model'] ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-4"><?= $mobil['nomor_plat'] ?></td>
                            <td class="py-3 px-4"><?= $mobil['jumlah_sewa'] ?? 0 ?></td>
                            <td class="py-3 px-4">
                                <?php
                                switch ($mobil['status']) {
                                    case 'tersedia': 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Tersedia</span>'; 
                                        break;
                                    case 'disewa': 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">Disewa</span>'; 
                                        break;
                                    case 'pemeliharaan': 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-800">Pemeliharaan</span>'; 
                                        break;
                                    default: 
                                        echo '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">Tidak Diketahui</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="py-3 px-6 border-t border-gray-100 bg-gray-50">
            <a href="<?= ADMIN_URL ?>mobil/index.php" class="text-primary-600 hover:text-primary-800 text-sm font-medium flex items-center">
                <span>Lihat semua mobil</span>
                <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data untuk grafik pemesanan
        const pemesananOptions = {
            series: [{
                name: 'Pemesanan',
                type: 'column',
                data: <?= json_encode($chartDataPemesanan) ?>
            }, {
                name: 'Pendapatan (dalam juta)',
                type: 'line',
                data: <?= json_encode(array_map(function($val) { return round($val / 1000000, 1); }, $chartDataPendapatan)) ?>
            }],
            chart: {
                height: 320,
                type: 'line',
                toolbar: {
                    show: false
                },
                fontFamily: 'Poppins, sans-serif'
            },
            stroke: {
                width: [0, 3]
            },
            dataLabels: {
                enabled: false
            },
            colors: ['#0EA5E9', '#10B981'],
            labels: <?= json_encode($labels) ?>,
            xaxis: {
                type: 'category',
                labels: {
                    style: {
                        fontFamily: 'Poppins, sans-serif'
                    }
                }
            },
            yaxis: [
                {
                    title: {
                        text: 'Jumlah Pemesanan',
                        style: {
                            fontFamily: 'Poppins, sans-serif'
                        }
                    },
                    labels: {
                        style: {
                            fontFamily: 'Poppins, sans-serif'
                        }
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Pendapatan (dalam juta)',
                        style: {
                            fontFamily: 'Poppins, sans-serif'
                        }
                    },
                    labels: {
                        formatter: function(val) {
                            return 'Rp ' + val.toFixed(1);
                        },
                        style: {
                            fontFamily: 'Poppins, sans-serif'
                        }
                    }
                }
            ],
            tooltip: {
                y: {
                    formatter: function(value, { seriesIndex }) {
                        if (seriesIndex === 1) {
                            return 'Rp ' + (value * 1000000).toLocaleString('id-ID');
                        }
                        return value.toLocaleString('id-ID');
                    }
                }
            },
            legend: {
                position: 'top',
                fontFamily: 'Poppins, sans-serif'
            }
        };
        
        const chartPemesanan = new ApexCharts(document.querySelector("#chartPemesanan"), pemesananOptions);
        chartPemesanan.render();
        
        // Data untuk grafik kategori
        const kategoriData = <?= json_encode(array_column($kategoriMobil, 'jumlah')) ?>;
        const kategoriLabels = <?= json_encode(array_column($kategoriMobil, 'nama')) ?>;
        
        const kategoriOptions = {
            series: kategoriData,
            chart: {
                type: 'donut',
                height: 160,
                fontFamily: 'Poppins, sans-serif'
            },
            labels: kategoriLabels,
            colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
            legend: {
                show: false
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' unit';
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '55%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            }
        };
        
        const chartKategori = new ApexCharts(document.querySelector("#chartKategori"), kategoriOptions);
        chartKategori.render();
    });
</script>

<?php require_once 'includes/footer.php'; ?> 