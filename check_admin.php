<?php
// Script untuk memeriksa dan membuat admin user

// Load config dan database
require_once 'config/database.php';

echo "Memeriksa user Admin...\n";
var_dump("Menjalankan script check_admin.php");

try {
    // Buat koneksi database
    $db = new Database();
    $conn = $db->getConnection();
    var_dump("Koneksi berhasil dibuat");
    
    // Cek apakah kolom role ada di tabel users
    $checkColStmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'role'
    ");
    $checkColStmt->execute();
    $roleExists = (int)$checkColStmt->fetchColumn() > 0;
    var_dump("roleExists: " . ($roleExists ? 'true' : 'false'));
    
    if (!$roleExists) {
        var_dump("Kolom 'role' tidak ditemukan di tabel users. Menambahkan kolom...");
        $alterStmt = $conn->prepare("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
        $alterStmt->execute();
        var_dump("Kolom 'role' berhasil ditambahkan.");
    } else {
        var_dump("Kolom 'role' sudah ada di tabel users.");
    }
    
    // Cek apakah user dengan ID 1 ada
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump("admin: ", $admin);
    
    if ($admin) {
        var_dump("User Admin ditemukan:");
        var_dump("ID: " . $admin['id']);
        var_dump("Username: " . $admin['username']);
        var_dump("Email: " . $admin['email']);
        var_dump("Role: " . ($admin['role'] ?? 'Tidak ada'));
        
        // Update role jika belum admin
        if (!isset($admin['role']) || $admin['role'] !== 'admin') {
            var_dump("Mengupdate role user ID 1 menjadi admin...");
            $updateStmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = 1");
            $updateStmt->execute();
            var_dump("Role berhasil diupdate.");
        }
    } else {
        var_dump("User Admin dengan ID 1 tidak ditemukan. Membuat user admin baru...");
        
        // Buat admin baru
        $insert = $conn->prepare("
            INSERT INTO users (id, username, email, password, role, status, created_at) 
            VALUES (1, 'admin', 'admin@rentalmobil.com', :password, 'admin', 'aktif', NOW())
        ");
        
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert->bindParam(':password', $password);
        $insert->execute();
        
        var_dump("User Admin berhasil dibuat dengan kredensial berikut:");
        var_dump("Username: admin");
        var_dump("Email: admin@rentalmobil.com");
        var_dump("Password: admin123");
    }
    
    // Periksa notifikasi untuk user admin
    $checkNotifStmt = $conn->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = 1");
    $checkNotifStmt->execute();
    $notifCount = (int)$checkNotifStmt->fetchColumn();
    
    var_dump("Jumlah notifikasi untuk admin (ID 1): " . $notifCount);
    
    // Jika admin tidak memiliki notifikasi, coba pindahkan dari user lain
    if ($notifCount == 0) {
        // Cek apakah ada notifikasi untuk user lain, misalnya ID 7 atau lainnya
        $checkOtherNotifStmt = $conn->prepare("SELECT user_id, COUNT(*) as count FROM notifikasi GROUP BY user_id");
        $checkOtherNotifStmt->execute();
        $otherNotifs = $checkOtherNotifStmt->fetchAll(PDO::FETCH_ASSOC);
        
        var_dump("Notifikasi dari user lain:");
        foreach ($otherNotifs as $notif) {
            var_dump("User ID " . $notif['user_id'] . ": " . $notif['count'] . " notifikasi");
            
            // Pindahkan ke admin jika admin tidak memiliki notifikasi
            if ($notifCount == 0 && $notif['user_id'] != 1 && $notif['count'] > 0) {
                var_dump("Memindahkan notifikasi dari user ID " . $notif['user_id'] . " ke admin (ID 1)...");
                $moveStmt = $conn->prepare("UPDATE notifikasi SET user_id = 1 WHERE user_id = :old_id");
                $moveStmt->bindParam(':old_id', $notif['user_id']);
                $moveStmt->execute();
                var_dump("Notifikasi berhasil dipindahkan.");
                break;
            }
        }
    }
    
    var_dump("Semua pemeriksaan selesai.");
} catch (PDOException $e) {
    var_dump("Error: " . $e->getMessage());
} 