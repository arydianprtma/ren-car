<?php
/**
 * Class Notification - Mengelola notifikasi untuk user
 */

// Debug statement
error_log("Loading Notification class...");

class Notification {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Mengambil semua notifikasi untuk user tertentu
     * 
     * @param int $userId ID user
     * @param int $limit Jumlah notifikasi yang diambil
     * @param int $offset Offset untuk pagination
     * @param bool $onlyUnread Hanya ambil notifikasi yang belum dibaca
     * @return array Daftar notifikasi
     */
    public function getUserNotifications($userId, $limit = 10, $offset = 0, $onlyUnread = false) {
        try {
            $sql = "
                SELECT * FROM notifikasi 
                WHERE user_id = :user_id 
            ";
            
            if ($onlyUnread) {
                $sql .= " AND status = 'belum_dibaca'";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Menghitung jumlah notifikasi yang belum dibaca
     * 
     * @param int $userId ID user
     * @return int Jumlah notifikasi yang belum dibaca
     */
    public function countUnreadNotifications($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM notifikasi 
                WHERE user_id = :user_id AND status = 'belum_dibaca'
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting unread notifications: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Menghitung jumlah notifikasi admin yang belum dibaca
     * 
     * @return int Jumlah notifikasi yang belum dibaca untuk admin
     */
    public function getUnreadAdminNotificationsCount() {
        try {
            // Jika admin aktif dalam session, gunakan ID admin tersebut
            if (isset($_SESSION['admin_id'])) {
                $count = $this->countUnreadNotifications($_SESSION['admin_id']);
                return is_numeric($count) ? (int)$count : 0;
            }
            
            // Jika tidak ada admin yang aktif, kembalikan 0
            return 0;
        } catch (Exception $e) {
            error_log("Error counting admin unread notifications: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Menandai notifikasi sebagai sudah dibaca
     * 
     * @param int $notificationId ID notifikasi
     * @param int $userId ID user (untuk keamanan)
     * @return bool
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifikasi 
                SET status = 'dibaca' 
                WHERE id = :id AND user_id = :user_id
            ");
            
            $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Menandai semua notifikasi user sebagai sudah dibaca
     * 
     * @param int $userId ID user
     * @return bool
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifikasi 
                SET status = 'dibaca' 
                WHERE user_id = :user_id
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mengirim notifikasi baru
     * 
     * @param int $userId ID user
     * @param string $judul Judul notifikasi
     * @param string $pesan Isi pesan
     * @param string $tipe Tipe notifikasi
     * @param int|null $referensiId ID referensi (opsional)
     * @param string|null $referensiTabel Tabel referensi (opsional)
     * @return bool
     */
    public function sendNotification($userId, $judul, $pesan, $tipe, $referensiId = null, $referensiTabel = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifikasi 
                (user_id, judul, pesan, tipe, referensi_id, referensi_tabel) 
                VALUES 
                (:user_id, :judul, :pesan, :tipe, :referensi_id, :referensi_tabel)
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':judul', $judul, PDO::PARAM_STR);
            $stmt->bindParam(':pesan', $pesan, PDO::PARAM_STR);
            $stmt->bindParam(':tipe', $tipe, PDO::PARAM_STR);
            $stmt->bindParam(':referensi_id', $referensiId, $referensiId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindParam(':referensi_tabel', $referensiTabel, $referensiTabel ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error sending notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mengirim notifikasi ke admin
     * 
     * @param string $judul Judul notifikasi
     * @param string $pesan Isi pesan
     * @param string $tipe Tipe notifikasi
     * @param int|null $referensiId ID referensi (opsional)
     * @param string|null $referensiTabel Tabel referensi (opsional)
     * @return bool
     */
    public function createAdminNotification($judul, $pesan, $tipe, $referensiId = null, $referensiTabel = null) {
        try {
            // Pertama, ambil semua admin dari database
            $admins = $this->getAdminUsers();
            
            if (empty($admins)) {
                error_log("No admin users found for notification");
                return false;
            }
            
            $success = true;
            // Kirim notifikasi ke semua admin
            foreach ($admins as $admin) {
                $result = $this->sendNotification($admin['id'], $judul, $pesan, $tipe, $referensiId, $referensiTabel);
                if (!$result) {
                    $success = false;
                }
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("Error creating admin notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mendapatkan daftar admin
     * 
     * @return array Daftar user admin
     */
    private function getAdminUsers() {
        try {
            // Debug - log SQL query untuk debugging
            error_log("Mencoba mencari admin dari database");
            
            // Cek apakah user dengan ID 1 ada
            $adminStmt = $this->db->prepare("SELECT id FROM users WHERE id = 1");
            $adminStmt->execute();
            $mainAdmin = $adminStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($mainAdmin) {
                error_log("Menemukan admin dengan ID 1: " . print_r($mainAdmin, true));
                return [$mainAdmin];
            }
            
            // Cek apakah kolom role ada dengan query yang aman
            $checkColStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'users' 
                AND COLUMN_NAME = 'role'
            ");
            $checkColStmt->execute();
            $roleExists = (int)$checkColStmt->fetchColumn() > 0;
            
            if (!$roleExists) {
                // Jika kolom role tidak ada, coba tambahkan
                try {
                    $alterStmt = $this->db->prepare("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
                    $alterStmt->execute();
                    $roleExists = true;
                    error_log("Kolom 'role' berhasil ditambahkan ke tabel users");
                    
                    // Update user dengan ID 1 menjadi admin
                    $updateStmt = $this->db->prepare("UPDATE users SET role = 'admin' WHERE id = 1");
                    $updateStmt->execute();
                    error_log("User ID 1 diupdate menjadi admin");
                    
                    // Ambil user dengan ID 1
                    $adminStmt = $this->db->prepare("SELECT id FROM users WHERE id = 1");
                    $adminStmt->execute();
                    $mainAdmin = $adminStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($mainAdmin) {
                        return [$mainAdmin];
                    }
                } catch (PDOException $e) {
                    error_log("Gagal menambahkan kolom 'role': " . $e->getMessage());
                }
            }
            
            if ($roleExists) {
                // Jika kolom role ada, gunakan itu
                $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin'");
                $stmt->execute();
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Ditemukan " . count($admins) . " admin berdasarkan kolom role");
                
                if (!empty($admins)) {
                    return $admins;
                }
            }
            
            // Fallback: coba cari admin berdasarkan username/email yang mengandung 'admin'
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username LIKE '%admin%' OR email LIKE '%admin%'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Ditemukan " . count($admins) . " admin berdasarkan username/email");
            
            if (!empty($admins)) {
                return $admins;
            }
            
            // Jika masih tidak menemukan admin, ambil user pertama sebagai admin
            $stmt = $this->db->prepare("SELECT id FROM users ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Menggunakan user pertama sebagai admin. Ditemukan: " . count($admins));
            
            // Secara opsional, update user pertama menjadi admin jika ditemukan
            if (!empty($admins) && $roleExists) {
                $updateStmt = $this->db->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
                $updateStmt->bindParam(':id', $admins[0]['id'], PDO::PARAM_INT);
                $updateStmt->execute();
                error_log("User ID {$admins[0]['id']} diupdate menjadi admin");
            }
            
            return $admins;
        } catch (PDOException $e) {
            error_log("Error getting admin users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mengirim notifikasi pengingat pembayaran
     */
    public function sendPaymentReminder($pemesananId) {
        try {
            $stmt = $this->db->prepare("CALL sp_kirim_notifikasi_pembayaran(:pemesanan_id)");
            $stmt->bindParam(':pemesanan_id', $pemesananId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Error sending payment reminder: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mengirim notifikasi konfirmasi pembayaran
     * 
     * @param int $pemesananId ID pemesanan
     * @param string $customerName Nama pelanggan
     * @param float $amount Jumlah pembayaran
     * @return bool
     */
    public function sendPaymentConfirmation($pemesananId, $customerName = "", $amount = 0) {
        $judul = "Pembayaran Menunggu Verifikasi";
        if (empty($customerName)) {
            $pesan = "Ada pembayaran baru yang menunggu verifikasi untuk pemesanan #{$pemesananId}.";
        } else {
            $pesan = "Pelanggan {$customerName} telah melakukan pembayaran sebesar Rp " . number_format($amount, 0, ',', '.') . " untuk pemesanan #{$pemesananId}. Silakan verifikasi.";
        }
        
        return $this->createAdminNotification($judul, $pesan, 'pembayaran', $pemesananId, 'pemesanan');
    }
    
    /**
     * Mengirim notifikasi pengingat pengembalian
     * 
     * @param int $pemesananId ID pemesanan
     * @param string $customerName Nama pelanggan
     * @param string $returnDate Tanggal pengembalian
     * @param string $mobilInfo Informasi mobil
     * @return bool
     */
    public function sendReturnReminder($pemesananId, $customerName = "", $returnDate = "", $mobilInfo = "") {
        $judul = "Pengingat Pengembalian Mobil";
        if (empty($customerName) || empty($returnDate) || empty($mobilInfo)) {
            $pesan = "Ada pengembalian mobil yang akan jatuh tempo untuk pemesanan #{$pemesananId}.";
        } else {
            $pesan = "Mobil {$mobilInfo} yang disewa oleh {$customerName} akan jatuh tempo pengembaliannya pada {$returnDate}. Silakan persiapkan proses pengembalian.";
        }
        
        return $this->createAdminNotification($judul, $pesan, 'pengembalian', $pemesananId, 'pemesanan');
    }
    
    /**
     * Menghapus notifikasi
     * 
     * @param int $notificationId ID notifikasi
     * @param int $userId ID user (untuk keamanan)
     * @return bool
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifikasi 
                WHERE id = :id AND user_id = :user_id
            ");
            
            $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Menghitung total notifikasi untuk seorang user
     * 
     * @param int $userId ID user
     * @return int Total notifikasi
     */
    public function getTotalUserNotifications($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM notifikasi 
                WHERE user_id = :user_id
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting total notifications: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mengirim notifikasi untuk pemesanan baru
     * 
     * @param int $pemesananId ID pemesanan baru
     * @param string $customerName Nama pelanggan
     * @return bool
     */
    public function sendNewOrderNotification($pemesananId, $customerName) {
        $judul = "Pemesanan Baru";
        $pesan = "Pelanggan {$customerName} telah membuat pemesanan baru. Silakan tinjau dan konfirmasi.";
        
        return $this->createAdminNotification($judul, $pesan, 'pesanan_baru', $pemesananId, 'pemesanan');
    }
    
    /**
     * Mengirim notifikasi pendaftaran user baru
     * 
     * @param int $userId ID user baru
     * @param string $userName Nama user
     * @param string $userEmail Email user
     * @return bool
     */
    public function sendNewUserNotification($userId, $userName, $userEmail) {
        $judul = "Pendaftaran User Baru";
        $pesan = "User baru telah mendaftar: {$userName} ({$userEmail}).";
        
        return $this->createAdminNotification($judul, $pesan, 'user_baru', $userId, 'users');
    }
    
    /**
     * Mengirim notifikasi sistem
     * 
     * @param string $judul Judul notifikasi
     * @param string $pesan Pesan notifikasi
     * @param int|null $referensiId ID referensi (opsional)
     * @param string|null $referensiTabel Tabel referensi (opsional)
     * @return bool
     */
    public function sendSystemNotification($judul, $pesan, $referensiId = null, $referensiTabel = null) {
        return $this->createAdminNotification($judul, $pesan, 'sistem', $referensiId, $referensiTabel);
    }
} 