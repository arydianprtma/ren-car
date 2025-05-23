<?php
// Script untuk menguji fungsi notifikasi pendaftaran user baru

require_once 'config/config.php';
require_once 'classes/Notification.php';

// Inisialisasi koneksi database
$database = new Database();
$conn = $database->getConnection();

// Tampilkan debug header untuk melihat output
header('Content-Type: text/plain');

try {
    if (!$conn) {
        throw new Exception("Koneksi database gagal");
    }
    
    // Cek apakah ada admin dengan kolom role
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    echo "Jumlah admin ditemukan: $adminCount\n";
    
    // Ambil daftar admin
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Daftar admin:\n";
    foreach ($admins as $admin) {
        echo "- ID: {$admin['id']}, Username: {$admin['username']}, Email: {$admin['email']}, Role: {$admin['role']}\n";
    }
    
    // Jika tidak ada admin dengan kolom role, coba cari berdasarkan username/email
    if (empty($admins)) {
        echo "\nMencari admin berdasarkan username/email yang mengandung 'admin'...\n";
        
        $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username LIKE '%admin%' OR email LIKE '%admin%'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($admins)) {
            echo "Ditemukan admin berdasarkan username/email:\n";
            foreach ($admins as $admin) {
                echo "- ID: {$admin['id']}, Username: {$admin['username']}, Email: {$admin['email']}\n";
                
                // Update user ini menjadi admin
                $updateStmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
                $updateStmt->bindParam(':id', $admin['id'], PDO::PARAM_INT);
                $updateStmt->execute();
                echo "  -> User diupdate menjadi admin\n";
            }
        } else {
            echo "Tidak ada user dengan username/email yang mengandung 'admin'\n";
            
            // Gunakan user pertama sebagai admin
            $stmt = $conn->prepare("SELECT id, username, email FROM users ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $firstUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($firstUser) {
                echo "\nMenggunakan user pertama sebagai admin:\n";
                echo "- ID: {$firstUser['id']}, Username: {$firstUser['username']}, Email: {$firstUser['email']}\n";
                
                // Update user pertama menjadi admin
                $updateStmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
                $updateStmt->bindParam(':id', $firstUser['id'], PDO::PARAM_INT);
                $updateStmt->execute();
                echo "  -> User diupdate menjadi admin\n";
            }
        }
    }
    
    // Test send notification
    echo "\nMengirim notifikasi user baru untuk testing...\n";
    $notification = new Notification($conn);
    $result = $notification->sendNewUserNotification(1, "Test User", "test@example.com");
    
    if ($result) {
        echo "Berhasil mengirim notifikasi!\n";
    } else {
        echo "Gagal mengirim notifikasi!\n";
    }
    
    // Periksa notifikasi yang dikirim
    $stmt = $conn->prepare("SELECT * FROM notifikasi WHERE tipe = 'user_baru' ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nNotifikasi terbaru tipe 'user_baru':\n";
    if (empty($notifications)) {
        echo "Tidak ada notifikasi ditemukan.\n";
    } else {
        foreach ($notifications as $notif) {
            echo "- ID: {$notif['id']}, User ID: {$notif['user_id']}, Judul: {$notif['judul']}, Pesan: {$notif['pesan']}, Status: {$notif['status']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 