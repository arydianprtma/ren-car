<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM admin WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    $password = 'admin123';
    if (password_verify($password, $admin['password'])) {
        echo "Password valid!\n";
        echo "Stored hash: " . $admin['password'] . "\n";
        echo "Verifying password 'admin123'\n";
    } else {
        echo "Password tidak valid!\n";
        echo "Stored hash: " . $admin['password'] . "\n";
        echo "Generated hash untuk 'admin123': " . password_hash('admin123', PASSWORD_BCRYPT) . "\n";
    }
} else {
    echo "Admin tidak ditemukan!\n";
} 