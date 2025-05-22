<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query('SELECT id, kode_pemesanan, status_pemesanan, metode_pembayaran FROM pemesanan LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo 'ID: ' . $row['id'] . ', Kode: ' . $row['kode_pemesanan'] . 
         ', Status: ' . $row['status_pemesanan'] . ', Metode: ' . 
         $row['metode_pembayaran'] . PHP_EOL;
}
?> 