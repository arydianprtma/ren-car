<?php
// Script untuk mengirim notifikasi pengingat pengembalian
// Dapat dijalankan melalui cron job: misalnya setiap hari jam 8 pagi
// Cron: 0 8 * * * php /path/to/admin/send_return_reminder.php

require_once '../config/config.php';
require_once '../classes/Notification.php';

// Inisialisasi database connection
$db = new Database();
$conn = $db->getConnection();

// Inisialisasi class Notification
$notification = new Notification($conn);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Dapatkan tanggal saat ini
$currentDate = date('Y-m-d');
// Juga ambil pemesanan yang akan berakhir 1 hari lagi
$reminderDate = date('Y-m-d', strtotime('+1 day'));

try {
    // Ambil semua pemesanan yang akan berakhir hari ini atau besok
    // dan statusnya masih berjalan
    $stmt = $conn->prepare("
        SELECT id, user_id, kode_pemesanan, tanggal_selesai 
        FROM pemesanan 
        WHERE (tanggal_selesai = :current_date OR tanggal_selesai = :reminder_date)
        AND status_pemesanan = 'berjalan'
    ");
    $stmt->bindParam(':current_date', $currentDate, PDO::PARAM_STR);
    $stmt->bindParam(':reminder_date', $reminderDate, PDO::PARAM_STR);
    $stmt->execute();
    
    $pemesanan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pemesanan_list) > 0) {
        foreach ($pemesanan_list as $pemesanan) {
            // Kirim notifikasi pengingat pengembalian
            $notification->sendReturnReminder($pemesanan['id']);
            
            echo "Notifikasi pengingat pengembalian telah dikirim untuk pemesanan " . $pemesanan['kode_pemesanan'] . "\n";
        }
    } else {
        echo "Tidak ada pemesanan yang perlu diingatkan pengembaliannya hari ini\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 