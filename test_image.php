<?php
// Path relatif untuk testing
$relative_path = 'assets/uploads/mobil/mobil_682eb55b3af74.jpeg';

// Path absolut untuk testing
$base_dir = __DIR__;
$absolute_path = $base_dir . '/' . $relative_path;

echo "<h2>Test Akses Gambar</h2>";
echo "<p>Base Directory: " . $base_dir . "</p>";
echo "<p>Path Relatif: " . $relative_path . "</p>";
echo "<p>Path Absolut: " . $absolute_path . "</p>";

// Cek file exists dengan path relatif
echo "<p>file_exists(relatif): " . (file_exists($relative_path) ? "Ada" : "Tidak ada") . "</p>";

// Cek file exists dengan path absolut
echo "<p>file_exists(absolut): " . (file_exists($absolute_path) ? "Ada" : "Tidak ada") . "</p>";

// Tampilkan gambar jika ada
if (file_exists($absolute_path)) {
    echo "<h3>Gambar (menggunakan path absolut):</h3>";
    echo "<img src='$relative_path' style='max-width: 300px;' />";
} else {
    echo "<p>Gambar tidak ditemukan di path yang diberikan.</p>";
}

// Tampilkan semua file di direktori uploads/mobil
echo "<h3>Daftar file di direktori uploads/mobil:</h3>";
$files = scandir('assets/uploads/mobil');
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";
?> 