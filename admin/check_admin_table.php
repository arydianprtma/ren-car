<?php
/**
 * Script untuk memeriksa tabel admin
 */
require_once '../config/config.php';

try {
    // Inisialisasi koneksi database
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Koneksi database gagal");
    }
    
    // Cek apakah tabel admin ada
    $stmt = $conn->query("SHOW TABLES LIKE 'admin'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>
            <h3>Tabel Admin Tidak Ditemukan</h3>
            <p>Tabel admin belum ada di database. Silakan jalankan script instalasi.</p>
        </div>";
        exit;
    }
    
    // Cek struktur tabel admin
    $stmt = $conn->query("DESCRIBE admin");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='padding: 20px; background-color: #d1ecf1; color: #0c5460; border-radius: 5px; margin: 20px;'>
        <h3>Struktur Tabel Admin</h3>
        <p>Tabel admin ditemukan dengan kolom-kolom berikut:</p>
        <ul>";
        
    foreach ($columns as $column) {
        echo "<li>{$column}</li>";
    }
    
    echo "</ul></div>";
    
    // Cek data admin
    $stmt = $conn->query("SELECT id, username, nama, email FROM admin");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($admins) > 0) {
        echo "<div style='padding: 20px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 20px;'>
            <h3>Data Admin</h3>
            <p>Berikut adalah data admin yang terdaftar (password tidak ditampilkan untuk keamanan):</p>
            <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                <tr style='background-color: #c3e6cb;'>
                    <th style='border: 1px solid #b1dfbb; padding: 8px; text-align: left;'>ID</th>
                    <th style='border: 1px solid #b1dfbb; padding: 8px; text-align: left;'>Username</th>
                    <th style='border: 1px solid #b1dfbb; padding: 8px; text-align: left;'>Nama</th>
                    <th style='border: 1px solid #b1dfbb; padding: 8px; text-align: left;'>Email</th>
                </tr>";
        
        foreach ($admins as $admin) {
            echo "<tr>
                <td style='border: 1px solid #b1dfbb; padding: 8px;'>{$admin['id']}</td>
                <td style='border: 1px solid #b1dfbb; padding: 8px;'>{$admin['username']}</td>
                <td style='border: 1px solid #b1dfbb; padding: 8px;'>{$admin['nama']}</td>
                <td style='border: 1px solid #b1dfbb; padding: 8px;'>{$admin['email']}</td>
            </tr>";
        }
        
        echo "</table>
        </div>";
        
        // Cek format password
        $stmt = $conn->query("SELECT username, password FROM admin");
        $adminPasswords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div style='padding: 20px; background-color: #fff3cd; color: #856404; border-radius: 5px; margin: 20px;'>
            <h3>Format Password</h3>
            <p>Ini akan membantu mendiagnosis masalah password:</p>
            <ul>";
        
        foreach ($adminPasswords as $admin) {
            $passwordInfo = password_get_info($admin['password']);
            $isHash = strlen($admin['password']) > 20 && $admin['password'][0] === '$';
            $algoritma = $isHash ? $passwordInfo['algoName'] : 'Bukan hash (plain text atau enkripsi lain)';
            
            echo "<li>
                <strong>Username:</strong> {$admin['username']}<br>
                <strong>Format password:</strong> {$algoritma}<br>
                <strong>Panjang password:</strong> " . strlen($admin['password']) . " karakter<br>
                <strong>Awalan password:</strong> " . substr($admin['password'], 0, 10) . "...<br>
            </li>";
        }
        
        echo "</ul>
            <p>Jika format password bukan merupakan hash yang valid (misalnya 'bcrypt' atau 'argon2i'), silakan reset password melalui <a href='reset_admin_password.php' style='color: #856404; text-decoration: underline;'>halaman reset password</a>.</p>
        </div>";
    } else {
        echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>
            <h3>Tidak Ada Data Admin</h3>
            <p>Belum ada data admin yang terdaftar. Silakan buat admin baru di <a href='reset_admin_password.php' style='color: #721c24; text-decoration: underline;'>halaman reset password</a>.</p>
        </div>";
    }
    
    echo "<div style='text-align: center; margin: 20px;'>
        <a href='reset_admin_password.php' style='display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Reset Password Admin</a>
        <a href='login.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Kembali ke Login</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>
        <h3>Error</h3>
        <p>{$e->getMessage()}</p>
        <p>Silakan coba lagi atau hubungi pengembang.</p>
    </div>";
    
    // Log error
    error_log("Check admin table error: " . $e->getMessage());
}
?> 