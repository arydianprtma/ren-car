<?php
/**
 * Setup script untuk menambah kolom reset password dan remember token
 * Jalankan file ini sekali untuk setup database
 */
require_once '../config/config.php';

echo "<h2>Setup Database untuk Fitur Lupa Password dan Ingat Saya</h2>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Cek apakah kolom sudah ada
    $checkColumns = $conn->prepare("
        SELECT COLUMN_NAME 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME IN ('reset_token', 'reset_token_expires', 'remember_token', 'remember_token_expires')
    ");
    $checkColumns->execute();
    $existingColumns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['reset_token', 'reset_token_expires', 'remember_token', 'remember_token_expires'];
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✓ Semua kolom sudah ada di database!</p>";
    } else {
        echo "<p>Menambahkan kolom yang diperlukan...</p>";
        
        // Tambahkan kolom yang belum ada
        $alterQueries = [];
        
        if (in_array('reset_token', $missingColumns)) {
            $alterQueries[] = "ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL";
        }
        
        if (in_array('reset_token_expires', $missingColumns)) {
            $alterQueries[] = "ADD COLUMN reset_token_expires TIMESTAMP NULL DEFAULT NULL";
        }
        
        if (in_array('remember_token', $missingColumns)) {
            $alterQueries[] = "ADD COLUMN remember_token VARCHAR(255) DEFAULT NULL";
        }
        
        if (in_array('remember_token_expires', $missingColumns)) {
            $alterQueries[] = "ADD COLUMN remember_token_expires TIMESTAMP NULL DEFAULT NULL";
        }
        
        if (!empty($alterQueries)) {
            $sql = "ALTER TABLE users " . implode(', ', $alterQueries);
            $conn->exec($sql);
            echo "<p style='color: green;'>✓ Kolom berhasil ditambahkan: " . implode(', ', $missingColumns) . "</p>";
        }
        
        // Tambahkan index untuk performa
        try {
            $conn->exec("CREATE INDEX idx_users_reset_token ON users(reset_token)");
            echo "<p style='color: green;'>✓ Index reset_token berhasil ditambahkan</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange;'>- Index reset_token sudah ada</p>";
            } else {
                throw $e;
            }
        }
        
        try {
            $conn->exec("CREATE INDEX idx_users_remember_token ON users(remember_token)");
            echo "<p style='color: green;'>✓ Index remember_token berhasil ditambahkan</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange;'>- Index remember_token sudah ada</p>";
            } else {
                throw $e;
            }
        }
    }
    
    echo "<h3>Status Kolom Database:</h3>";
    $stmt = $conn->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Kolom</th><th>Tipe</th><th>Null</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        $highlight = in_array($column['Field'], $requiredColumns) ? ' style="background-color: #e8f5e8;"' : '';
        echo "<tr{$highlight}>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Setup Selesai!</h3>";
    echo "<p>Fitur Lupa Password dan Ingat Saya sekarang sudah siap digunakan.</p>";
    echo "<p><a href='login.php'>← Kembali ke Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 