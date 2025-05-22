<?php
/**
 * Database Configuration
 */
class Database {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $database = "rental_mobil";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->user,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Koneksi database gagal: " . $e->getMessage();
        }
        return $this->conn;
    }
} 