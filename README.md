# Dokumentasi Sistem Rental Mobil

## Deskripsi Sistem

Sistem Rental Mobil adalah aplikasi berbasis web yang dikembangkan untuk manajemen bisnis penyewaan mobil. Sistem ini dibangun menggunakan PHP dengan database MySQL, dan menggunakan Tailwind CSS untuk tampilan yang modern dan responsif.

Aplikasi ini menyediakan dua akses utama:
1. **Panel Admin**: Untuk mengelola data mobil, kategori, pemesanan, pengembalian, dan laporan
2. **Situs Frontend**: Untuk pelanggan melakukan pencarian, pemesanan, dan pembayaran mobil

## Fitur Utama

### 1. Panel Admin
- **Manajemen Mobil**: Tambah, edit, hapus, dan lihat detail mobil
- **Manajemen Kategori**: Kelola kategori mobil (Sedan, SUV, MPV, dll)
- **Manajemen Pemesanan**: Lihat, konfirmasi, tolak, dan proses pembayaran pemesanan
- **Manajemen Pengembalian**: Proses pengembalian mobil dan hitung denda otomatis
- **Manajemen User**: Kelola data pelanggan dan admin
- **Profil Admin**: Edit informasi dan keamanan akun admin
- **Laporan**: Generate laporan pemesanan, pendapatan, mobil populer, dan user aktif
- **Export PDF**: Laporan dapat diunduh dalam format PDF

### 2. Frontend Pelanggan
- **Registrasi & Login**: Buat akun dan masuk ke sistem
- **Pencarian Mobil**: Filter berdasarkan kategori, tanggal, dan harga
- **Detail Mobil**: Informasi lengkap tentang mobil (spesifikasi, harga, fitur)
- **Pemesanan**: Proses pemesanan dengan konfirmasi
- **Pembayaran**: Upload bukti transfer dan konfirmasi pembayaran
- **Riwayat Pemesanan**: Lihat dan kelola riwayat pemesanan
- **Profil User**: Edit informasi pribadi dan kata sandi

## Teknologi yang Digunakan

- **Backend**: PHP 7.4+ (Native)
- **Database**: MySQL/MariaDB
- **Frontend**: HTML, JavaScript, Tailwind CSS
- **PDF Generation**: mPDF
- **Chart/Visualisasi**: Chart.js
- **Icon**: Font Awesome
- **Deployment**: Server lokal (Laragon/XAMPP)

## Struktur Database

Berikut adalah tabel utama dalam database:

1. **users**: Data pengguna (admin dan pelanggan)
2. **mobil**: Data mobil yang tersedia untuk disewa
3. **kategori**: Kategori mobil (SUV, Sedan, dll)
4. **pemesanan**: Data pemesanan mobil
5. **pembayaran**: Data pembayaran untuk pemesanan
6. **notifikasi**: Notifikasi sistem

## Cara Instalasi

### Prasyarat
- PHP 7.4 atau lebih tinggi
- MySQL/MariaDB
- Composer
- Web Server (Apache/Nginx)

### Langkah Instalasi

1. **Clone repositori**
   ```
   git clone https://github.com/yourusername/rental-mobil.git
   cd rental-mobil
   ```

2. **Instal dependensi PHP**
   ```
   composer install
   ```

3. **Konfigurasi database**
   - Buat database MySQL baru
   - Impor file SQL dari `database/rental_mobil.sql`
   - Sesuaikan konfigurasi database di `config/database.php`

4. **Konfigurasi aplikasi**
   - Sesuaikan pengaturan di `config/config.php`
   - Atur BASE_URL dan path lainnya

5. **Akses aplikasi**
   - Buka browser dan akses URL sesuai konfigurasi
   - Login admin default:
     - Username: admin
     - Password: admin123

## Implementasi Konsep Sistem Terdistribusi

Sistem Rental Mobil menerapkan beberapa konsep sistem terdistribusi:

1. **Client-Server Architecture**:
   - Server (backend PHP) melayani permintaan dari client (browser)
   - Pemisahan antara logika bisnis (server) dan presentasi (client)

2. **Distributed Database**:
   - Database terpisah dari aplikasi, bisa diakses dari beberapa instance aplikasi

3. **Stateless Communication**:
   - Komunikasi HTTP antara client-server bersifat stateless
   - Session digunakan untuk mempertahankan state pengguna

4. **RESTful API Principles**:
   - Komunikasi backend-frontend menggunakan prinsip RESTful
   - Format data JSON untuk pertukaran data

5. **Fault Tolerance**:
   - Validasi input di sisi client dan server
   - Penanganan error dan exception

## Modul Fitur Laporan

### Deskripsi
Modul laporan menyediakan informasi statistik dan analitik tentang operasional rental mobil. Admin dapat melihat berbagai data seperti tren pemesanan, pendapatan, mobil populer, dan user aktif.

### Cara Penggunaan Modul Laporan

1. **Mengakses Halaman Laporan**:
   - Login sebagai admin
   - Klik menu "Laporan" di sidebar

2. **Memilih Jenis Laporan**:
   - Laporan Pemesanan: Menampilkan jumlah pemesanan per hari
   - Laporan Pendapatan: Menampilkan total pendapatan per hari
   - Laporan Mobil Populer: Menampilkan mobil dengan jumlah sewa terbanyak
   - Laporan User Aktif: Menampilkan user dengan jumlah sewa terbanyak

3. **Memfilter Data**:
   - Pilih tanggal awal dan akhir untuk periode laporan
   - Klik tombol "Filter" untuk menampilkan data

4. **Mengexport ke PDF**:
   - Klik tombol "Export PDF" untuk mengunduh laporan dalam format PDF
   - PDF berisi data statistik dan tabel detail sesuai jenis laporan

### Implementasi Teknis

1. **File Utama**:
   - `admin/laporan/index.php`: Halaman utama laporan dengan filter dan grafik
   - `admin/laporan/export_pdf.php`: Skrip untuk menghasilkan file PDF

2. **Dependensi**:
   - Chart.js: Untuk visualisasi data dalam bentuk grafik
   - mPDF: Untuk generate laporan PDF

3. **Query Database**:
   - Query agregasi untuk menghitung statistik (COUNT, SUM, GROUP BY)
   - JOIN antar tabel untuk mendapatkan data relasional

## Analisis Sistem

### Kelebihan
1. **Antarmuka yang Intuitif**: Desain UI/UX yang mudah digunakan
2. **Fitur Komprehensif**: Mencakup seluruh siklus bisnis rental mobil
3. **Responsif**: Dapat diakses dari berbagai perangkat
4. **Visualisasi Data**: Grafik dan statistik memudahkan analisis bisnis
5. **Keamanan**: Validasi input dan otentikasi pengguna

### Keterbatasan
1. **Skalabilitas**: Arsitektur monolitik dapat membatasi skalabilitas
2. **Ketergantungan Jaringan**: Memerlukan koneksi internet yang stabil
3. **Integrasi Terbatas**: Belum terintegrasi dengan sistem pembayaran online atau GPS tracking

## Panduan untuk Dokumen Laporan Akhir

Dalam menyusun laporan akhir untuk tugas sistem terdistribusi, sebaiknya menyertakan:

1. **Pendahuluan**:
   - Latar belakang pembuatan sistem
   - Tujuan dan manfaat
   - Ruang lingkup

2. **Kajian Teori**:
   - Konsep dasar sistem terdistribusi
   - Teknologi web (PHP, MySQL, JavaScript)
   - Arsitektur client-server

3. **Analisis dan Perancangan**:
   - Analisis kebutuhan (fungsional dan non-fungsional)
   - Diagram alur sistem
   - Perancangan database (ERD)
   - Perancangan antarmuka

4. **Implementasi**:
   - Struktur kode program
   - Screenshot dan penjelasan fitur utama
   - Penjelasan implementasi konsep terdistribusi

5. **Pengujian**:
   - Skenario pengujian
   - Hasil pengujian
   - Evaluasi kinerja sistem

6. **Penutup**:
   - Kesimpulan
   - Saran pengembangan lebih lanjut

## Contoh Kode

### Contoh Query Laporan

```php
// Query untuk laporan pemesanan per hari
$stmt = $conn->prepare("
    SELECT DATE(created_at) as tanggal, COUNT(*) as jumlah, SUM(total_harga) as pendapatan
    FROM pemesanan
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY tanggal
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Contoh Implementasi Chart

```javascript
// Konfigurasi chart untuk laporan
const config = {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
        datasets: [{
            label: 'Jumlah Pemesanan',
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
};
```

## Kesimpulan

Sistem Rental Mobil mendemonstrasikan implementasi prinsip-prinsip sistem terdistribusi dalam konteks aplikasi web praktis. Dengan fitur komprehensif dan antarmuka yang intuitif, sistem ini dapat meningkatkan efisiensi operasional bisnis rental mobil dan memberikan pengalaman yang lebih baik untuk pelanggan.

---

**Developed by:** [Nama Tim/Pengembang]  
**Version:** 1.0  
**Last Updated:** Mei 2025 