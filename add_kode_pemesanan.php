<?php
require_once 'config/database.php';

// Inisialisasi koneksi database
$db = new Database();
$conn = $db->getConnection();

try {
    // Cek apakah kolom kode_pemesanan sudah ada
    $checkColumn = $conn->query("SHOW COLUMNS FROM pemesanan LIKE 'kode_pemesanan'");
    
    if ($checkColumn->rowCount() > 0) {
        echo "Kolom kode_pemesanan sudah ada dalam tabel pemesanan.";
    } else {
        // Tambahkan kolom kode_pemesanan
        $conn->exec("ALTER TABLE pemesanan ADD COLUMN kode_pemesanan VARCHAR(20) NOT NULL UNIQUE AFTER id");
        echo "Kolom kode_pemesanan berhasil ditambahkan!";
    }
    
    // Cek apakah kolom catatan_admin sudah ada
    $checkAdminNotes = $conn->query("SHOW COLUMNS FROM pemesanan LIKE 'catatan_admin'");
    
    if ($checkAdminNotes->rowCount() > 0) {
        echo "<br>Kolom catatan_admin sudah ada dalam tabel pemesanan.";
    } else {
        // Tambahkan kolom catatan_admin
        $conn->exec("ALTER TABLE pemesanan ADD COLUMN catatan_admin TEXT AFTER catatan");
        echo "<br>Kolom catatan_admin berhasil ditambahkan!";
    }
    
    // Ubah metode_pembayaran agar bisa NULL
    $conn->exec("ALTER TABLE pemesanan MODIFY COLUMN metode_pembayaran ENUM('transfer_bank', 'tunai', 'e-wallet') NULL");
    echo "<br>Kolom metode_pembayaran berhasil diubah agar bisa NULL!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 