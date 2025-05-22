<?php
/**
 * Class Notification - Mengelola notifikasi untuk user
 */
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
     * @return array Daftar notifikasi
     */
    public function getUserNotifications($userId, $limit = 10, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifikasi 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset
            ");
            
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
     */
    public function sendPaymentConfirmation($pemesananId) {
        try {
            $stmt = $this->db->prepare("CALL sp_kirim_notifikasi_konfirmasi(:pemesanan_id)");
            $stmt->bindParam(':pemesanan_id', $pemesananId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Error sending payment confirmation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mengirim notifikasi pengingat pengembalian
     */
    public function sendReturnReminder($pemesananId) {
        try {
            $stmt = $this->db->prepare("CALL sp_kirim_notifikasi_pengembalian(:pemesanan_id)");
            $stmt->bindParam(':pemesanan_id', $pemesananId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Error sending return reminder: " . $e->getMessage());
            return false;
        }
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
} 