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
    google_id VARCHAR(100) DEFAULT NULL,
    foto_ktp VARCHAR(255),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    role ENUM('admin', 'user') DEFAULT 'user',
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(255) DEFAULT NULL,
    remember_token_expires TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tambahkan index untuk token
CREATE INDEX IF NOT EXISTS idx_users_reset_token ON users(reset_token);
CREATE INDEX IF NOT EXISTS idx_users_remember_token ON users(remember_token);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

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
    status_pemesanan ENUM('menunggu', 'dibayar', 'dikonfirmasi', 'berjalan', 'pending_return', 'selesai', 'dibatalkan') DEFAULT 'menunggu',
    metode_pembayaran ENUM('transfer_bank', 'tunai', 'e-wallet', 'midtrans'),
    bukti_pembayaran VARCHAR(255),
    midtrans_token VARCHAR(255) NULL COMMENT 'Token pembayaran dari Midtrans',
    midtrans_order_id VARCHAR(255) NULL COMMENT 'Order ID yang dikirim ke Midtrans',
    midtrans_id VARCHAR(255) NULL COMMENT 'ID transaksi dari Midtrans',
    midtrans_status VARCHAR(50) NULL COMMENT 'Status transaksi dari Midtrans',
    midtrans_payment_type VARCHAR(50) NULL COMMENT 'Jenis pembayaran dari Midtrans',
    midtrans_bank VARCHAR(50) NULL COMMENT 'Bank yang digunakan untuk pembayaran',
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
    tipe VARCHAR(50) NOT NULL COMMENT 'user_baru, pesanan_baru, pembayaran, pengembalian, sistem',
    status ENUM('dibaca', 'belum_dibaca') DEFAULT 'belum_dibaca',
    referensi_id INT NULL COMMENT 'ID referensi ke tabel lain jika ada',
    referensi_tabel VARCHAR(100) NULL COMMENT 'Nama tabel referensi',
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

-- Stored Procedure untuk mengirim notifikasi pembayaran
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_kirim_notifikasi_pembayaran(IN p_pemesanan_id INT)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_kode_pemesanan VARCHAR(20);
    
    -- Ambil data pemesanan
    SELECT user_id, kode_pemesanan INTO v_user_id, v_kode_pemesanan
    FROM pemesanan
    WHERE id = p_pemesanan_id;
    
    -- Kirim notifikasi
    INSERT INTO notifikasi (user_id, judul, pesan, tipe, referensi_id, referensi_tabel)
    VALUES (v_user_id, 'Menunggu Pembayaran', 
            CONCAT('Pesanan ', v_kode_pemesanan, ' Anda menunggu pembayaran. Silakan lakukan pembayaran sebelum batas waktu.'),
            'pembayaran', p_pemesanan_id, 'pemesanan');
END //
DELIMITER ;

-- Stored Procedure untuk mengirim notifikasi konfirmasi pembayaran
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_kirim_notifikasi_konfirmasi(IN p_pemesanan_id INT)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_kode_pemesanan VARCHAR(20);
    
    -- Ambil data pemesanan
    SELECT user_id, kode_pemesanan INTO v_user_id, v_kode_pemesanan
    FROM pemesanan
    WHERE id = p_pemesanan_id;
    
    -- Kirim notifikasi
    INSERT INTO notifikasi (user_id, judul, pesan, tipe, referensi_id, referensi_tabel)
    VALUES (v_user_id, 'Pembayaran Dikonfirmasi', 
            CONCAT('Pembayaran untuk pesanan ', v_kode_pemesanan, ' telah dikonfirmasi. Terima kasih.'),
            'konfirmasi', p_pemesanan_id, 'pemesanan');
END //
DELIMITER ;

-- Stored Procedure untuk mengirim notifikasi pengingat pengembalian
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_kirim_notifikasi_pengembalian(IN p_pemesanan_id INT)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_kode_pemesanan VARCHAR(20);
    DECLARE v_tanggal_selesai DATE;
    
    -- Ambil data pemesanan
    SELECT user_id, kode_pemesanan, tanggal_selesai INTO v_user_id, v_kode_pemesanan, v_tanggal_selesai
    FROM pemesanan
    WHERE id = p_pemesanan_id;
    
    -- Kirim notifikasi
    INSERT INTO notifikasi (user_id, judul, pesan, tipe, referensi_id, referensi_tabel)
    VALUES (v_user_id, 'Pengingat Pengembalian', 
            CONCAT('Pesanan ', v_kode_pemesanan, ' akan berakhir pada ', DATE_FORMAT(v_tanggal_selesai, '%d %M %Y'), '. Mohon mengembalikan mobil sesuai jadwal.'),
            'pengembalian', p_pemesanan_id, 'pemesanan');
END //
DELIMITER ;


-- Tambahkan Data Admin Default
INSERT INTO admin (username, password, nama, email, no_telp) 
VALUES ('admin', '$2y$10$q8zJz.3Vh8B.PwNJRXvUxe7XY03S9K6ioOqfRO.NTjZdH7MlXD4t2', 'Administrator', 'admin@rentalmobil.com', '081234567890');
-- Password: admin123

-- Tambahkan Data User Admin Default
INSERT INTO users (username, password, nama, email, no_telp, alamat, no_ktp, role) 
VALUES ('admin', '$2y$10$q8zJz.3Vh8B.PwNJRXvUxe7XY03S9K6ioOqfRO.NTjZdH7MlXD4t2', 'Administrator', 'admin@rentalmobil.com', '081234567890', 'Jl. Admin No. 1', '1234567890123456', 'admin');
-- Password: admin123

-- Tambahkan Data Kategori Mobil
INSERT INTO kategori_mobil (nama_kategori, deskripsi) VALUES
('SUV', 'Sport Utility Vehicle dengan ground clearance tinggi'),
('MPV', 'Multi Purpose Vehicle untuk keluarga'),
('Sedan', 'Mobil penumpang dengan bagasi terpisah'),
('Hatchback', 'Mobil penumpang dengan bagasi menyatu'),
('Pickup', 'Kendaraan pengangkut barang dengan bak terbuka');

-- Tambahkan Data Mobil
INSERT INTO mobil (kategori_id, merk, model, tahun_produksi, nomor_plat, warna, kapasitas, transmisi, bahan_bakar, harga_sewa_per_hari, status, deskripsi, foto_mobil, fitur) VALUES
(1, 'Toyota', 'Rush', 2022, 'B 1234 CD', 'Putih', 7, 'otomatis', 'bensin', 500000.00, 'tersedia', 'Toyota Rush keluaran terbaru dengan fitur lengkap', 'rush.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": true, "gps": false}'),
(2, 'Honda', 'Mobilio', 2021, 'B 2345 DE', 'Hitam', 7, 'manual', 'bensin', 450000.00, 'tersedia', 'Honda Mobilio nyaman untuk perjalanan keluarga', 'mobilio.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": false, "gps": false}'),
(3, 'Toyota', 'Avanza', 2020, 'B 3456 EF', 'Silver', 7, 'manual', 'bensin', 400000.00, 'tersedia', 'Toyota Avanza andalan keluarga Indonesia', 'avanza.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": false, "gps": false}'),
(4, 'Honda', 'Civic', 2022, 'B 4567 FG', 'Merah', 5, 'otomatis', 'bensin', 700000.00, 'tersedia', 'Honda Civic terbaru dengan performa tinggi', 'civic.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": true, "gps": true}'),
(5, 'Toyota', 'Fortuner', 2022, 'B 5678 GH', 'Putih', 7, 'otomatis', 'diesel', 1000000.00, 'tersedia', 'Toyota Fortuner SUV premium dengan tenaga besar', 'fortuner.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": true, "gps": true}'),
(2, 'Mitsubishi', 'Xpander', 2021, 'B 6789 HI', 'Silver', 7, 'otomatis', 'bensin', 500000.00, 'tersedia', 'Mitsubishi Xpander dengan desain futuristik', 'xpander.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": true, "gps": false}'),
(3, 'Daihatsu', 'Xenia', 2020, 'B 7890 IJ', 'Putih', 7, 'manual', 'bensin', 375000.00, 'tersedia', 'Daihatsu Xenia ekonomis dan nyaman', 'xenia.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": false, "gps": false}'),
(4, 'Toyota', 'Yaris', 2021, 'B 8901 JK', 'Kuning', 5, 'otomatis', 'bensin', 450000.00, 'tersedia', 'Toyota Yaris compact dan gesit', 'yaris.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": false, "gps": false}'),
(5, 'Mitsubishi', 'Pajero Sport', 2021, 'B 9012 KL', 'Hitam', 7, 'otomatis', 'diesel', 950000.00, 'tersedia', 'Mitsubishi Pajero Sport tangguh di segala medan', 'pajero.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": true, "gps": true}'),
(1, 'Honda', 'HR-V', 2021, 'B 0123 LM', 'Merah', 5, 'otomatis', 'bensin', 600000.00, 'tersedia', 'Honda HR-V stylish dan sporty', 'hrv.jpg', '{"ac": true, "airbag": true, "bluetooth": true, "backup_camera": true, "gps": false}');

-- Tambahkan Data User Dummy
INSERT INTO users (username, password, nama, email, no_telp, alamat, no_ktp, role) VALUES
('user1', '$2y$10$q8zJz.3Vh8B.PwNJRXvUxe7XY03S9K6ioOqfRO.NTjZdH7MlXD4t2', 'Budi Santoso', 'budi@gmail.com', '081234567891', 'Jl. Merdeka No. 10, Jakarta', '3171234567890001', 'user'),
('user2', '$2y$10$q8zJz.3Vh8B.PwNJRXvUxe7XY03S9K6ioOqfRO.NTjZdH7MlXD4t2', 'Siti Rahayu', 'siti@gmail.com', '081234567892', 'Jl. Sudirman No. 45, Jakarta', '3171234567890002', 'user'),
('user3', '$2y$10$q8zJz.3Vh8B.PwNJRXvUxe7XY03S9K6ioOqfRO.NTjZdH7MlXD4t2', 'Joko Widodo', 'joko@gmail.com', '081234567893', 'Jl. Pemuda No. 12, Surabaya', '3571234567890003', 'user');
-- Password: admin123 