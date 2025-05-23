<?php
/**
 * Export Laporan ke PDF - Admin Panel
 */
require_once '../includes/auth_check.php';
require_once '../../config/database.php';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Ambil parameter
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'pemesanan';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validasi tanggal
if (!$start_date || !strtotime($start_date)) {
    $start_date = date('Y-m-01');
}
if (!$end_date || !strtotime($end_date)) {
    $end_date = date('Y-m-d');
}

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

// Ambil data sesuai jenis filter
$report_data = [];
$report_title = '';

switch ($filter_type) {
    case 'pemesanan':
        $report_title = 'Laporan Pemesanan';
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
        break;

    case 'pendapatan':
        $report_title = 'Laporan Pendapatan';
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
        break;
    
    case 'mobil':
        $report_title = 'Laporan Mobil Populer';
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
        break;
    
    case 'user':
        $report_title = 'Laporan User Aktif';
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
        break;
}

// Ambil statistik dasar
$basicStats = getBasicStats($conn, $start_date, $end_date);

// Mulai generate PDF dengan mPDF
require_once '../../vendor/autoload.php';

// Cek apakah vendor/autoload.php ada
if (!file_exists('../../vendor/autoload.php')) {
    echo "Error: File vendor/autoload.php tidak ditemukan. Pastikan Anda sudah menginstal mPDF dengan Composer.";
    echo "<p>Silahkan jalankan perintah berikut di terminal project Anda:</p>";
    echo "<pre>composer require mpdf/mpdf</pre>";
    exit;
}

// Inisialisasi mPDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10
]);

// Judul PDF dan metadata
$mpdf->SetTitle($report_title . ' - Rental Mobil');
$mpdf->SetAuthor('Admin Rental Mobil');

// Format tanggal untuk judul
$formatted_start = date('d/m/Y', strtotime($start_date));
$formatted_end = date('d/m/Y', strtotime($end_date));

// Mulai HTML untuk PDF
$html = '
<style>
    body {
        font-family: sans-serif;
        font-size: 10pt;
    }
    h1 {
        font-size: 16pt;
        text-align: center;
        margin-bottom: 4pt;
    }
    h2 {
        font-size: 14pt;
        margin-top: 10pt;
        margin-bottom: 6pt;
        color: #333;
    }
    .subtitle {
        font-size: 11pt;
        text-align: center;
        margin-bottom: 15pt;
        color: #666;
    }
    .header {
        text-align: center;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10pt;
        margin-bottom: 20pt;
    }
    .logo {
        font-size: 20pt;
        font-weight: bold;
        color: #0284c7;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15pt;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 6pt;
        text-align: left;
    }
    th {
        background-color: #f3f4f6;
        font-weight: bold;
    }
    .stats-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15pt;
    }
    .stats-box {
        width: 23%;
        padding: 10pt;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 5pt;
    }
    .stats-box-title {
        font-size: 9pt;
        color: #666;
        margin-bottom: 3pt;
    }
    .stats-box-value {
        font-size: 14pt;
        font-weight: bold;
        color: #333;
    }
    .footer {
        padding-top: 5pt;
        border-top: 1px solid #ddd;
        font-size: 8pt;
        text-align: center;
        color: #666;
    }
    .text-right {
        text-align: right;
    }
</style>

<div class="header">
    <div class="logo">RENTAL MOBIL</div>
    <div>' . $report_title . '</div>
</div>

<h1>' . $report_title . '</h1>
<div class="subtitle">Periode: ' . $formatted_start . ' s/d ' . $formatted_end . '</div>

<table style="width: 100%; margin-bottom: 15pt;">
    <tr>
        <td style="width: 25%; border: 1px solid #ddd; padding: 8pt; background-color: #f3f4f6;">Total Pemesanan</td>
        <td style="width: 25%; border: 1px solid #ddd; padding: 8pt;">' . number_format($basicStats['total_pemesanan']) . '</td>
        <td style="width: 25%; border: 1px solid #ddd; padding: 8pt; background-color: #f3f4f6;">Total Pendapatan</td>
        <td style="width: 25%; border: 1px solid #ddd; padding: 8pt;">Rp ' . number_format($basicStats['total_pendapatan'], 0, ',', '.') . '</td>
    </tr>
    <tr>
        <td style="width: 25%; border: 1px solid #ddd; padding: 8pt; background-color: #f3f4f6;">Total Denda</td>
        <td style="width: 25%; border: 1px solid #ddd; padding: 8pt;">Rp ' . number_format($basicStats['total_denda'], 0, ',', '.') . '</td>
        <td style="width: 25%; border: 1px solid #ddd; padding: 8pt; background-color: #f3f4f6;">User Baru</td>
        <td style="width: 25%; border: 1px solid #ddd; padding: 8pt;">' . number_format($basicStats['user_baru']) . '</td>
    </tr>
</table>

<h2>Detail Data</h2>';

// Tambahkan tabel data sesuai jenis laporan
if ($filter_type === 'pemesanan' || $filter_type === 'pendapatan') {
    $html .= '
    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Tanggal</th>
                <th style="width: 40%;">Jumlah Pemesanan</th>
                <th style="width: 40%;">Pendapatan</th>
            </tr>
        </thead>
        <tbody>';
    
    $totalJumlah = 0;
    $totalPendapatan = 0;
    
    if (!empty($report_data)) {
        foreach ($report_data as $item) {
            $html .= '
            <tr>
                <td>' . date('d/m/Y', strtotime($item['tanggal'])) . '</td>
                <td class="text-right">' . number_format($item['jumlah']) . '</td>
                <td class="text-right">Rp ' . number_format($item['pendapatan'], 0, ',', '.') . '</td>
            </tr>';
            
            $totalJumlah += $item['jumlah'];
            $totalPendapatan += $item['pendapatan'];
        }
    } else {
        $html .= '<tr><td colspan="3" style="text-align: center;">Tidak ada data yang ditemukan</td></tr>';
    }
    
    // Tambahkan total
    $html .= '
        <tr style="font-weight: bold; background-color: #f3f4f6;">
            <td>Total</td>
            <td class="text-right">' . number_format($totalJumlah) . '</td>
            <td class="text-right">Rp ' . number_format($totalPendapatan, 0, ',', '.') . '</td>
        </tr>';
    
    $html .= '
        </tbody>
    </table>';
} elseif ($filter_type === 'mobil') {
    $html .= '
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Mobil</th>
                <th style="width: 30%;">Jumlah Sewa</th>
                <th style="width: 30%;">Pendapatan</th>
            </tr>
        </thead>
        <tbody>';
    
    $totalJumlah = 0;
    $totalPendapatan = 0;
    
    if (!empty($report_data)) {
        foreach ($report_data as $item) {
            $html .= '
            <tr>
                <td>' . $item['merk'] . ' ' . $item['model'] . '<br><span style="font-size: 8pt; color: #666;">' . $item['nomor_plat'] . '</span></td>
                <td class="text-right">' . number_format($item['jumlah_sewa']) . '</td>
                <td class="text-right">Rp ' . number_format($item['pendapatan'], 0, ',', '.') . '</td>
            </tr>';
            
            $totalJumlah += $item['jumlah_sewa'];
            $totalPendapatan += $item['pendapatan'];
        }
    } else {
        $html .= '<tr><td colspan="3" style="text-align: center;">Tidak ada data yang ditemukan</td></tr>';
    }
    
    // Tambahkan total
    $html .= '
        <tr style="font-weight: bold; background-color: #f3f4f6;">
            <td>Total</td>
            <td class="text-right">' . number_format($totalJumlah) . '</td>
            <td class="text-right">Rp ' . number_format($totalPendapatan, 0, ',', '.') . '</td>
        </tr>';
    
    $html .= '
        </tbody>
    </table>';
} elseif ($filter_type === 'user') {
    $html .= '
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Nama User</th>
                <th style="width: 30%;">Jumlah Sewa</th>
                <th style="width: 30%;">Total Pengeluaran</th>
            </tr>
        </thead>
        <tbody>';
    
    $totalJumlah = 0;
    $totalPengeluaran = 0;
    
    if (!empty($report_data)) {
        foreach ($report_data as $item) {
            $html .= '
            <tr>
                <td>' . $item['nama'] . '<br><span style="font-size: 8pt; color: #666;">' . $item['email'] . '</span></td>
                <td class="text-right">' . number_format($item['jumlah_sewa']) . '</td>
                <td class="text-right">Rp ' . number_format($item['total_pengeluaran'], 0, ',', '.') . '</td>
            </tr>';
            
            $totalJumlah += $item['jumlah_sewa'];
            $totalPengeluaran += $item['total_pengeluaran'];
        }
    } else {
        $html .= '<tr><td colspan="3" style="text-align: center;">Tidak ada data yang ditemukan</td></tr>';
    }
    
    // Tambahkan total
    $html .= '
        <tr style="font-weight: bold; background-color: #f3f4f6;">
            <td>Total</td>
            <td class="text-right">' . number_format($totalJumlah) . '</td>
            <td class="text-right">Rp ' . number_format($totalPengeluaran, 0, ',', '.') . '</td>
        </tr>';
    
    $html .= '
        </tbody>
    </table>';
}

// Tambahkan footer
$html .= '
<div class="footer">
    <p>Laporan dibuat pada: ' . date('d/m/Y H:i:s') . '</p>
    <p>Rental Mobil © ' . date('Y') . ' - Sistem Informasi Rental Mobil</p>
</div>';

// Tulis ke PDF
$mpdf->WriteHTML($html);

// Output PDF ke browser
$mpdf->Output($report_title . ' - ' . date('d-m-Y') . '.pdf', 'I');
exit; 