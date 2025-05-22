<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Memperbaiki pemesanan dengan status kosong
try {
    // Mulai transaksi
    $conn->beginTransaction();
    
    // Tampilkan semua data pemesanan untuk debugging
    $all_stmt = $conn->query('SELECT id, kode_pemesanan, status_pemesanan, metode_pembayaran FROM pemesanan');
    $all_pemesanan = $all_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total pemesanan dalam database: " . count($all_pemesanan) . PHP_EOL;
    foreach ($all_pemesanan as $p) {
        echo "ID: " . $p['id'] . ", Kode: " . $p['kode_pemesanan'] . 
             ", Status: '" . $p['status_pemesanan'] . "', Metode: '" . $p['metode_pembayaran'] . "'" . PHP_EOL;
    }
    
    // Menghitung pemesanan dengan status kosong tapi memiliki metode pembayaran
    $stmt = $conn->query("SELECT COUNT(*) FROM pemesanan WHERE (status_pemesanan = '' OR status_pemesanan IS NULL) AND metode_pembayaran IS NOT NULL AND metode_pembayaran != ''");
    $count = $stmt->fetchColumn();
    
    echo "Pemesanan dengan status kosong tapi memiliki metode pembayaran: " . $count . PHP_EOL;
    
    // Update semua pemesanan yang memiliki metode pembayaran tapi status kosong
    $update_sql = "UPDATE pemesanan SET status_pemesanan = 'dikonfirmasi' WHERE (status_pemesanan = '' OR status_pemesanan IS NULL) AND metode_pembayaran IS NOT NULL AND metode_pembayaran != ''";
    $update_result = $conn->exec($update_sql);
    
    echo "Jumlah pemesanan yang diperbarui: " . $update_result . PHP_EOL;
    
    // Commit transaksi
    $conn->commit();
    echo "Selesai memperbaiki status pemesanan.";
    
} catch (PDOException $e) {
    // Rollback transaksi jika ada error
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?> 