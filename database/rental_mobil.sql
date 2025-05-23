-- Database untuk Sistem Rental Mobil
CREATE DATABASE IF NOT EXISTS rental_mobil;
USE rental_mobil;

-- Tabel Admin
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_telp VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel User/Pelanggan
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_telp VARCHAR(15) NOT NULL,
    alamat TEXT NOT NULL,
    no_ktp VARCHAR(20) NOT NULL,
    foto_ktp VARCHAR(255),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Kategori Mobil
CREATE TABLE IF NOT EXISTS kategori_mobil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Mobil
CREATE TABLE IF NOT EXISTS mobil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT,
    merk VARCHAR(50) NOT NULL,
    model VARCHAR(100) NOT NULL,
    tahun_produksi YEAR NOT NULL,
    nomor_plat VARCHAR(20) NOT NULL UNIQUE,
    warna VARCHAR(30) NOT NULL,
    kapasitas INT NOT NULL,
    transmisi ENUM('manual', 'otomatis') NOT NULL,
    bahan_bakar ENUM('bensin', 'diesel', 'listrik', 'hybrid') NOT NULL,
    harga_sewa_per_hari DECIMAL(10,2) NOT NULL,
    status ENUM('tersedia', 'disewa', 'pemeliharaan') DEFAULT 'tersedia',
    deskripsi TEXT,
    foto_mobil VARCHAR(255),
    fitur JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_mobil(id) ON DELETE SET NULL
);

-- Tabel Pemesanan/Rental
CREATE TABLE IF NOT EXISTS pemesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pemesanan VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    mobil_id INT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    status_pemesanan ENUM('menunggu', 'dikonfirmasi', 'berjalan', 'selesai', 'dibatalkan') DEFAULT 'menunggu',
    metode_pembayaran ENUM('transfer_bank', 'tunai', 'e-wallet', 'midtrans'),
    bukti_pembayaran VARCHAR(255),
    midtrans_token VARCHAR(255),
    midtrans_order_id VARCHAR(100),
    midtrans_status VARCHAR(50),
    denda DECIMAL(10,2) DEFAULT 0,
    catatan TEXT,
    catatan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mobil_id) REFERENCES mobil(id) ON DELETE CASCADE
);

-- Tabel Pengembalian
CREATE TABLE IF NOT EXISTS pengembalian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pemesanan_id INT NOT NULL,
    tanggal_pengembalian DATE NOT NULL,
    kondisi_mobil TEXT NOT NULL,
    denda DECIMAL(10,2) DEFAULT 0.00,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pemesanan_id) REFERENCES pemesanan(id) ON DELETE CASCADE
);

-- Tabel Ulasan/Review
CREATE TABLE IF NOT EXISTS ulasan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mobil_id INT NOT NULL,
    pemesanan_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mobil_id) REFERENCES mobil(id) ON DELETE CASCADE,
    FOREIGN KEY (pemesanan_id) REFERENCES pemesanan(id) ON DELETE CASCADE
);

-- Tabel Notifikasi
CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    tipe ENUM('user_baru', 'pemesanan', 'pembayaran', 'pengembalian', 'sistem') NOT NULL,
    status ENUM('belum_dibaca', 'dibaca') DEFAULT 'belum_dibaca',
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Midtrans Notification
CREATE TABLE IF NOT EXISTS midtrans_notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL,
    status_code VARCHAR(10),
    transaction_status VARCHAR(50),
    fraud_status VARCHAR(50),
    payment_type VARCHAR(50),
    gross_amount DECIMAL(10,2),
    raw_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tambahkan Data Admin Default
INSERT INTO admin (username, password, nama, email, no_telp) 
VALUES ('admin', '$2y$10$q8zJz.3Vh8B.PwNJRXvUxe7XY03S9K6ioOqfRO.NTjZdH7MlXD4t2', 'Administrator', 'admin@rentalmobil.com', '081234567890');
-- Password: admin123

-- Tambahkan Data User Admin Default
INSERT INTO users (username, password, nama, email, no_telp, alamat, no_ktp, role) 
VALUES ('admin', '$2y$10$q8zJz.3Vh8B.PwNJRXvUxe7XY03S9K6ioOqfRO.NTjZdH7MlXD4t2', 'Administrator', 'admin@rentalmobil.com', '081234567890', 'Jl. Admin No. 1', '1234567890123456', 'admin');
-- Password: admin123 