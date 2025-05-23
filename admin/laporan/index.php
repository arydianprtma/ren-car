<?php
/**
 * Halaman Laporan - Admin Panel
 */
require_once '../includes/auth_check.php';
require_once '../includes/header.php';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Filter periode laporan
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default: awal bulan ini
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default: hari ini
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'pemesanan'; // Default: pemesanan

// Validasi tanggal
if (!$start_date || !strtotime($start_date)) {
    $start_date = date('Y-m-01');
}
if (!$end_date || !strtotime($end_date)) {
    $end_date = date('Y-m-d');
}

// Ambil data laporan sesuai filter
$report_data = [];
$chart_labels = [];
$chart_data = [];

// Fungsi untuk mendapatkan statistik dasar
function getBasicStats($conn, $start_date, $end_date)
{
    // Total pemesanan pada periode
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pemesanan WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $totalPemesanan = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total pendapatan pada periode
    $stmt = $conn->prepare("SELECT SUM(total_harga) as total FROM pemesanan WHERE status_pemesanan IN ('dikonfirmasi', 'berjalan', 'selesai') AND created_at BETWEEN ? AND ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $totalPendapatan = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total denda pada periode
    $stmt = $conn->prepare("SELECT SUM(denda) as total FROM pemesanan WHERE denda > 0 AND tanggal_selesai BETWEEN ? AND ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $totalDenda = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // User baru pada periode
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $userBaru = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    return [
        'total_pemesanan' => $totalPemesanan,
        'total_pendapatan' => $totalPendapatan,
        'total_denda' => $totalDenda,
        'user_baru' => $userBaru
    ];
}

$basicStats = getBasicStats($conn, $start_date, $end_date);

// Ambil data sesuai jenis filter
switch ($filter_type) {
    case 'pemesanan':
        // Data pemesanan per hari
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as tanggal, COUNT(*) as jumlah, SUM(total_harga) as pendapatan
            FROM pemesanan
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY tanggal
        ");
        $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Data untuk grafik
        foreach ($report_data as $item) {
            $chart_labels[] = date('d/m/Y', strtotime($item['tanggal']));
            $chart_data[] = (int)$item['jumlah'];
        }
        break;

    case 'pendapatan':
        // Data pendapatan per hari
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as tanggal, SUM(total_harga) as pendapatan, COUNT(*) as jumlah
            FROM pemesanan
            WHERE status_pemesanan IN ('dikonfirmasi', 'berjalan', 'selesai') AND created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY tanggal
        ");
        $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Data untuk grafik
        foreach ($report_data as $item) {
            $chart_labels[] = date('d/m/Y', strtotime($item['tanggal']));
            $chart_data[] = (int)$item['pendapatan'];
        }
        break;
    
    case 'mobil':
        // Data mobil paling banyak disewa
        $stmt = $conn->prepare("
            SELECT m.id, m.merk, m.model, m.nomor_plat, COUNT(p.id) as jumlah_sewa, SUM(p.total_harga) as pendapatan
            FROM mobil m
            LEFT JOIN pemesanan p ON m.id = p.mobil_id AND p.created_at BETWEEN ? AND ?
            WHERE p.id IS NOT NULL
            GROUP BY m.id
            ORDER BY jumlah_sewa DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Data untuk grafik
        foreach ($report_data as $item) {
            $chart_labels[] = $item['merk'] . ' ' . $item['model'];
            $chart_data[] = (int)$item['jumlah_sewa'];
        }
        break;
    
    case 'user':
        // Data user dengan pemesanan terbanyak
        $stmt = $conn->prepare("
            SELECT u.id, u.nama, u.email, COUNT(p.id) as jumlah_sewa, SUM(p.total_harga) as total_pengeluaran
            FROM users u
            JOIN pemesanan p ON u.id = p.user_id AND p.created_at BETWEEN ? AND ?
            GROUP BY u.id
            ORDER BY jumlah_sewa DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Data untuk grafik
        foreach ($report_data as $item) {
            $chart_labels[] = $item['nama'];
            $chart_data[] = (int)$item['jumlah_sewa'];
        }
        break;
}
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800 flex items-center">
        <i class="fas fa-chart-bar mr-3 text-primary-600"></i> Laporan
    </h1>
    
    <div>
        <a href="<?= ADMIN_URL ?>laporan/export_pdf.php?filter_type=<?= $filter_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center inline-flex mr-2">
            <i class="fas fa-file-pdf mr-2"></i> Export PDF
        </a>
    </div>
</div>

<!-- Filter dan Pencarian -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-wrap items-end gap-4">
        <div class="w-full md:w-1/5">
            <label for="filter_type" class="block text-sm font-medium text-gray-700 mb-1">Jenis Laporan</label>
            <select id="filter_type" name="filter_type" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="pemesanan" <?= $filter_type === 'pemesanan' ? 'selected' : '' ?>>Laporan Pemesanan</option>
                <option value="pendapatan" <?= $filter_type === 'pendapatan' ? 'selected' : '' ?>>Laporan Pendapatan</option>
                <option value="mobil" <?= $filter_type === 'mobil' ? 'selected' : '' ?>>Laporan Mobil Populer</option>
                <option value="user" <?= $filter_type === 'user' ? 'selected' : '' ?>>Laporan User Aktif</option>
            </select>
        </div>
        
        <div class="w-full md:w-1/5">
            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
            <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        
        <div class="w-full md:w-1/5">
            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
            <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        
        <div class="flex items-center space-x-2">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <a href="<?= ADMIN_URL ?>laporan/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg shadow-sm transition duration-200 flex items-center">
                <i class="fas fa-sync-alt mr-2"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Informasi Ringkasan -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <!-- Total Pemesanan -->
    <div class="bg-white rounded-lg shadow-sm p-6 stats-card flex items-center border-l-4 border-primary-500">
        <div class="p-3 rounded-full bg-primary-100 text-primary-600 mr-4">
            <i class="fas fa-clipboard-list text-xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Total Pemesanan</p>
            <p class="text-2xl font-bold"><?= number_format($basicStats['total_pemesanan']) ?></p>
        </div>
    </div>
    
    <!-- Total Pendapatan -->
    <div class="bg-white rounded-lg shadow-sm p-6 stats-card flex items-center border-l-4 border-green-500">
        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
            <i class="fas fa-money-bill-wave text-xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Total Pendapatan</p>
            <p class="text-xl font-bold">Rp <?= number_format($basicStats['total_pendapatan'], 0, ',', '.') ?></p>
        </div>
    </div>
    
    <!-- Total Denda -->
    <div class="bg-white rounded-lg shadow-sm p-6 stats-card flex items-center border-l-4 border-red-500">
        <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
            <i class="fas fa-exclamation-circle text-xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Total Denda</p>
            <p class="text-xl font-bold">Rp <?= number_format($basicStats['total_denda'], 0, ',', '.') ?></p>
        </div>
    </div>
    
    <!-- User Baru -->
    <div class="bg-white rounded-lg shadow-sm p-6 stats-card flex items-center border-l-4 border-amber-500">
        <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
            <i class="fas fa-user-plus text-xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm">User Baru</p>
            <p class="text-2xl font-bold"><?= number_format($basicStats['user_baru']) ?></p>
        </div>
    </div>
</div>

<!-- Grafik dan Data -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
    <!-- Grafik -->
    <div class="bg-white rounded-lg shadow-sm p-6 lg:col-span-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
            <?php if ($filter_type === 'pemesanan'): ?>
                Grafik Jumlah Pemesanan
            <?php elseif ($filter_type === 'pendapatan'): ?>
                Grafik Pendapatan
            <?php elseif ($filter_type === 'mobil'): ?>
                Grafik Mobil Populer
            <?php elseif ($filter_type === 'user'): ?>
                Grafik User Aktif
            <?php endif; ?>
        </h2>
        
        <div class="h-80">
            <canvas id="reportChart"></canvas>
        </div>
    </div>
    
    <!-- Data Tabel -->
    <div class="bg-white rounded-lg shadow-sm p-6 lg:col-span-4">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan Data</h2>
        
        <div class="overflow-x-auto">
            <?php if ($filter_type === 'pemesanan' || $filter_type === 'pendapatan'): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal
                            </th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah
                            </th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pendapatan
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500">
                                    Tidak ada data yang ditemukan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($report_data as $item): ?>
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d/m/Y', strtotime($item['tanggal'])) ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-right">
                                        <?= number_format($item['jumlah']) ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-right">
                                        Rp <?= number_format($item['pendapatan'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php elseif ($filter_type === 'mobil'): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Mobil
                            </th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah Sewa
                            </th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pendapatan
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500">
                                    Tidak ada data yang ditemukan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($report_data as $item): ?>
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-800">
                                        <?= $item['merk'] ?> <?= $item['model'] ?>
                                        <div class="text-xs text-gray-500"><?= $item['nomor_plat'] ?></div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-right">
                                        <?= number_format($item['jumlah_sewa']) ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-right">
                                        Rp <?= number_format($item['pendapatan'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php elseif ($filter_type === 'user'): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama User
                            </th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah Sewa
                            </th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500">
                                    Tidak ada data yang ditemukan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($report_data as $item): ?>
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-800">
                                        <?= $item['nama'] ?>
                                        <div class="text-xs text-gray-500"><?= $item['email'] ?></div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-right">
                                        <?= number_format($item['jumlah_sewa']) ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-right">
                                        Rp <?= number_format($item['total_pengeluaran'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('reportChart').getContext('2d');
    
    // Konfigurasi warna yang sesuai dengan tema
    let backgroundColor = 'rgba(59, 130, 246, 0.2)';
    let borderColor = 'rgba(59, 130, 246, 1)';
    
    <?php if ($filter_type === 'pendapatan'): ?>
    backgroundColor = 'rgba(16, 185, 129, 0.2)';
    borderColor = 'rgba(16, 185, 129, 1)';
    <?php elseif ($filter_type === 'mobil'): ?>
    backgroundColor = 'rgba(245, 158, 11, 0.2)';
    borderColor = 'rgba(245, 158, 11, 1)';
    <?php elseif ($filter_type === 'user'): ?>
    backgroundColor = 'rgba(139, 92, 246, 0.2)';
    borderColor = 'rgba(139, 92, 246, 1)';
    <?php endif; ?>
    
    // Data untuk chart
    const chartData = {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: '<?= ($filter_type === 'pemesanan' ? 'Jumlah Pemesanan' : 
                     ($filter_type === 'pendapatan' ? 'Pendapatan (Rp)' : 
                     ($filter_type === 'mobil' ? 'Jumlah Sewa' : 'Jumlah Pemesanan'))) ?>',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: backgroundColor,
            borderColor: borderColor,
            borderWidth: 1
        }]
    };
    
    // Konfigurasi chart
    const config = {
        type: '<?= ($filter_type === 'pemesanan' || $filter_type === 'pendapatan') ? 'line' : 'bar' ?>',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        <?php if ($filter_type === 'pendapatan'): ?>
                        callback: function(value) {
                            return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        }
                        <?php endif; ?>
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    };
    
    // Buat chart
    const myChart = new Chart(ctx, config);
});
</script>

<?php require_once '../includes/footer.php'; ?> 