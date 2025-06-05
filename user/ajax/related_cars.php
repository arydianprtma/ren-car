<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Ambil parameter
$id_mobil = isset($_GET['id']) ? $_GET['id'] : 0;
$kategori_id = isset($_GET['kategori']) ? $_GET['kategori'] : 0;

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

// Query untuk mobil terkait berdasarkan kategori yang sama
$sql = "SELECT m.*, k.nama_kategori FROM mobil m 
        LEFT JOIN kategori_mobil k ON m.kategori_id = k.id 
        WHERE m.id != ? ";

$params = [$id_mobil];

// Jika ada kategori yang dipilih
if (!empty($kategori_id)) {
    $sql .= "AND m.kategori_id = ? ";
    $params[] = $kategori_id;
}

// Batasi hanya 3 mobil terkait
$sql .= "ORDER BY RAND() LIMIT 3";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $relatedCars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Jika tidak ada mobil terkait, ambil mobil acak
    if (empty($relatedCars)) {
        $stmt = $conn->prepare("SELECT m.*, k.nama_kategori FROM mobil m 
                               LEFT JOIN kategori_mobil k ON m.kategori_id = k.id 
                               WHERE m.id != ? 
                               ORDER BY RAND() LIMIT 3");
        $stmt->execute([$id_mobil]);
        $relatedCars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo '<p class="text-center text-red-600">Gagal memuat mobil terkait: ' . $e->getMessage() . '</p>';
    exit;
}

// Jika tidak ada mobil sama sekali, tampilkan pesan
if (empty($relatedCars)) {
    echo '<p class="text-center text-gray-600 py-6">Tidak ada mobil terkait yang tersedia saat ini.</p>';
    exit;
}

// Ambil informasi pemesanan untuk mobil yang sedang disewa
$mobilDisewa = [];
$sql = "SELECT mobil_id, tanggal_selesai 
        FROM pemesanan 
        WHERE status_pemesanan NOT IN ('dibatalkan', 'selesai') 
        AND tanggal_selesai >= CURRENT_DATE()";
$stmt = $conn->query($sql);
$pemesananAktif = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buat array untuk menyimpan tanggal pengembalian mobil
foreach ($pemesananAktif as $pemesanan) {
    $mobilDisewa[$pemesanan['mobil_id']] = $pemesanan['tanggal_selesai'];
}

// Tampilkan mobil terkait
foreach ($relatedCars as $mobil): ?>
    <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg border border-gray-100 hover:border-blue-100 group animate-fadeIn">
        <div class="h-48 bg-gray-200 relative overflow-hidden">
            <?php if (!empty($mobil['foto_mobil'])): ?>
                <img src="<?= ASSETS_URL ?>uploads/mobil/<?= $mobil['foto_mobil'] ?>" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 <?= ($mobil['status'] != 'tersedia') ? 'opacity-70' : '' ?>">
            <?php else: ?>
                <img src="<?= ASSETS_URL ?>images/car-login.jpg" alt="<?= $mobil['merk'] ?> <?= $mobil['model'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
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
        <div class="p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors"><?= $mobil['merk'] ?> <?= $mobil['model'] ?></h3>
            <div class="flex items-center text-gray-600 text-sm mb-3">
                <span class="flex items-center mr-4"><i class="fas fa-car mr-2 text-blue-500"></i> <?= $mobil['tahun_produksi'] ?></span>
                <span class="flex items-center"><i class="fas fa-user mr-2 text-blue-500"></i> <?= $mobil['kapasitas'] ?> Orang</span>
            </div>
            <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                <div class="text-blue-600 font-bold">
                    Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?>
                </div>
                <?php if ($mobil['status'] == 'tersedia'): ?>
                    <a href="detail-mobil.php?id=<?= $mobil['id'] ?>" class="bg-blue-600 text-white px-4 py-1 rounded-lg hover:bg-blue-700 transition-all text-sm">Detail</a>
                <?php else: ?>
                    <a href="detail-mobil.php?id=<?= $mobil['id'] ?>" class="bg-yellow-600 text-white px-4 py-1 rounded-lg hover:bg-yellow-700 transition-all text-sm">Lihat Detail</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?> 