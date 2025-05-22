<?php
// Tampilkan semua error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test Akses Gambar di Browser</h1>";

// 1. Periksa file di direktori uploads/mobil
echo "<h2>1. Periksa Direktori uploads/mobil</h2>";
if (is_dir('assets/uploads/mobil')) {
    echo "<p style='color:green'>Direktori assets/uploads/mobil ditemukan.</p>";
    
    // Tampilkan semua file
    echo "<h3>File di direktori:</h3>";
    $files = scandir('assets/uploads/mobil');
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file";
            $filepath = 'assets/uploads/mobil/' . $file;
            echo " (file_exists: " . (file_exists($filepath) ? 'Ya' : 'Tidak') . ")";
            echo "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>Direktori assets/uploads/mobil tidak ditemukan!</p>";
}

// 2. Test file_exists dengan berbagai metode
echo "<h2>2. Test file_exists() dengan Berbagai Path</h2>";

// Test file pertama jika ada
$firstFile = '';
$files = scandir('assets/uploads/mobil');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $firstFile = $file;
        break;
    }
}

if (!empty($firstFile)) {
    $relativePath = 'assets/uploads/mobil/' . $firstFile;
    $absolutePath = __DIR__ . '/' . $relativePath;
    $realPath = realpath($relativePath);
    
    echo "<p>File: <strong>$firstFile</strong></p>";
    echo "<p>Path Relatif: $relativePath (exists: " . (file_exists($relativePath) ? 'Ya' : 'Tidak') . ")</p>";
    echo "<p>Path Absolut: $absolutePath (exists: " . (file_exists($absolutePath) ? 'Ya' : 'Tidak') . ")</p>";
    echo "<p>Real Path: " . ($realPath ? $realPath : 'Tidak dapat resolusi') . " (exists: " . ($realPath && file_exists($realPath) ? 'Ya' : 'Tidak') . ")</p>";
}

// 3. Test tampilan gambar dengan berbagai path
echo "<h2>3. Test Tampilan Gambar</h2>";
if (!empty($firstFile)) {
    echo "<h3>Gambar dengan path relatif:</h3>";
    echo "<img src='assets/uploads/mobil/$firstFile' style='max-width: 300px; border: 1px solid #ccc;' />";
    
    echo "<h3>Test gambar placeholder:</h3>";
    echo "<img src='assets/images/car-login.jpg' style='max-width: 300px; border: 1px solid #ccc;' />";
}

// 4. Test path file_exists dengan ../ (seperti yang digunakan di file-file user/)
echo "<h2>4. Test file_exists() dengan ../ (dari perspektif direktori user/)</h2>";
echo "<p>Simulasi: jika file ini diakses dari direktori user/, seperti code yang digunakan di file-file user/</p>";

if (!empty($firstFile)) {
    $userPerspectivePath = '../assets/uploads/mobil/' . $firstFile;
    $userAbsolutePath = dirname(__DIR__) . '/assets/uploads/mobil/' . $firstFile;
    
    echo "<p>Path ../assets/uploads/mobil/$firstFile dari user/ (simulasi exists: " . (file_exists($userAbsolutePath) ? 'Ya' : 'Tidak') . ")</p>";
}

// 5. Informasi variabel server
echo "<h2>5. Informasi Server</h2>";
echo "<p>DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>PHP_SELF: " . $_SERVER['PHP_SELF'] . "</p>";
?> 