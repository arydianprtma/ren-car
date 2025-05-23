-- Cek apakah kolom role sudah ada di tabel users
SET @exist := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'role'
);

-- Jika kolom belum ada, tambahkan kolom
SET @query = IF(@exist = 0, 
    'ALTER TABLE users ADD COLUMN role ENUM("admin", "user") DEFAULT "user" AFTER alamat;',
    'SELECT "Kolom role sudah ada di tabel users" AS message;'
);

-- Eksekusi query
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update user pertama menjadi admin jika belum ada admin
UPDATE users
SET role = 'admin'
WHERE id = (SELECT id FROM (SELECT MIN(id) as id FROM users) as subquery)
AND NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin');

-- Tambahkan index untuk mempercepat pencarian berdasarkan role
SET @indexexist := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'idx_users_role'
);

SET @indexquery = IF(@indexexist = 0, 
    'CREATE INDEX idx_users_role ON users(role);',
    'SELECT "Index idx_users_role sudah ada di tabel users" AS message;'
);

-- Eksekusi query untuk index
PREPARE stmt FROM @indexquery;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 