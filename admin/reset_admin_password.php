<?php
/**
 * Script untuk mereset password admin
 * Gunakan script ini untuk memperbaiki masalah password admin
 */
require_once '../config/config.php';

// Set password default
$default_username = 'admin';
$default_password = 'admin123';

try {
    // Inisialisasi koneksi database
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Koneksi database gagal");
    }
    
    // Cek apakah admin sudah ada
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = :username");
    $stmt->bindParam(':username', $default_username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Admin sudah ada, perbarui password
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        $admin_id = $admin['id'];
        
        // Hash password baru
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        
        // Update password
        $update = $conn->prepare("UPDATE admin SET password = :password WHERE id = :id");
        $update->bindParam(':password', $hashed_password);
        $update->bindParam(':id', $admin_id);
        
        if ($update->execute()) {
            echo "<div style='padding: 20px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 20px;'>
                <h3>Password Admin Berhasil Direset</h3>
                <p>Username: {$default_username}</p>
                <p>Password: {$default_password}</p>
                <p>Silakan login dengan kredensial di atas.</p>
                <p><a href='".ADMIN_URL."login.php' style='color: #155724; text-decoration: underline;'>Kembali ke Login</a></p>
            </div>";
        } else {
            throw new Exception("Gagal mengupdate password admin");
        }
    } else {
        // Admin belum ada, buat admin baru
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        $nama = "Administrator";
        $email = "admin@example.com";
        
        $insert = $conn->prepare("INSERT INTO admin (username, password, nama, email) VALUES (:username, :password, :nama, :email)");
        $insert->bindParam(':username', $default_username);
        $insert->bindParam(':password', $hashed_password);
        $insert->bindParam(':nama', $nama);
        $insert->bindParam(':email', $email);
        
        if ($insert->execute()) {
            echo "<div style='padding: 20px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 20px;'>
                <h3>Admin Baru Berhasil Dibuat</h3>
                <p>Username: {$default_username}</p>
                <p>Password: {$default_password}</p>
                <p>Silakan login dengan kredensial di atas.</p>
                <p><a href='".ADMIN_URL."login.php' style='color: #155724; text-decoration: underline;'>Kembali ke Login</a></p>
            </div>";
        } else {
            throw new Exception("Gagal membuat admin baru");
        }
    }
} catch (Exception $e) {
    echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>
        <h3>Error</h3>
        <p>{$e->getMessage()}</p>
        <p>Silakan coba lagi atau hubungi pengembang.</p>
        <p><a href='".ADMIN_URL."login.php' style='color: #721c24; text-decoration: underline;'>Kembali ke Login</a></p>
    </div>";
    
    // Log error
    error_log("Reset admin password error: " . $e->getMessage());
}
?> 