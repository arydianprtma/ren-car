-- Tabel untuk menyimpan notifikasi
CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    tipe VARCHAR(50) NOT NULL COMMENT 'pembayaran, pengembalian, konfirmasi, umum, dll',
    status ENUM('dibaca', 'belum_dibaca') DEFAULT 'belum_dibaca',
    referensi_id INT NULL COMMENT 'ID referensi ke tabel lain jika ada',
    referensi_tabel VARCHAR(100) NULL COMMENT 'Nama tabel referensi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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