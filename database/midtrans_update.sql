-- Periksa apakah kolom-kolom Midtrans sudah ada
SET @exist_midtrans_token = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pemesanan' AND COLUMN_NAME = 'midtrans_token');
SET @exist_midtrans_order_id = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pemesanan' AND COLUMN_NAME = 'midtrans_order_id');
SET @exist_midtrans_id = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pemesanan' AND COLUMN_NAME = 'midtrans_id');
SET @exist_midtrans_status = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pemesanan' AND COLUMN_NAME = 'midtrans_status');
SET @exist_midtrans_payment_type = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pemesanan' AND COLUMN_NAME = 'midtrans_payment_type');
SET @exist_midtrans_bank = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pemesanan' AND COLUMN_NAME = 'midtrans_bank');

-- Tambahkan kolom jika belum ada
ALTER TABLE pemesanan
ADD COLUMN midtrans_token VARCHAR(255) NULL COMMENT 'Token pembayaran dari Midtrans' AFTER bukti_pembayaran,
ADD COLUMN midtrans_order_id VARCHAR(255) NULL COMMENT 'Order ID yang dikirim ke Midtrans' AFTER midtrans_token,
ADD COLUMN midtrans_id VARCHAR(255) NULL COMMENT 'ID transaksi dari Midtrans' AFTER midtrans_order_id,
ADD COLUMN midtrans_status VARCHAR(50) NULL COMMENT 'Status transaksi dari Midtrans' AFTER midtrans_id,
ADD COLUMN midtrans_payment_type VARCHAR(50) NULL COMMENT 'Jenis pembayaran dari Midtrans' AFTER midtrans_status,
ADD COLUMN midtrans_bank VARCHAR(50) NULL COMMENT 'Bank yang digunakan untuk pembayaran' AFTER midtrans_payment_type;

-- Periksa apakah metode_pembayaran sudah memiliki opsi 'midtrans'
SET @column_type = (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pemesanan' AND COLUMN_NAME = 'metode_pembayaran');
SET @has_midtrans = (SELECT IF(@column_type LIKE '%midtrans%', 1, 0));

-- Update ENUM metode_pembayaran jika belum memiliki opsi midtrans
ALTER TABLE pemesanan 
MODIFY COLUMN metode_pembayaran ENUM('transfer_bank', 'tunai', 'e-wallet', 'midtrans') NULL;