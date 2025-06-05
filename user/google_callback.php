<?php
require_once '../config/config.php';

// Fungsi untuk membuat username unik dari nama
function createUniqueUsername($name) {
    // Hapus spasi dan karakter khusus, gunakan nama depan saja
    $name = preg_replace('/[^a-zA-Z0-9]/', '', $name);
    $name = strtolower(substr($name, 0, 10)); // ambil maksimal 10 karakter
    
    // Tambahkan angka random
    $randomNumber = rand(1000, 9999);
    return $name . $randomNumber;
}

try {
    // Initialize Google Client
    $client = getGoogleClient();
    
    // Jika ada kode otorisasi dari Google
    if (isset($_GET['code'])) {
        // Mendapatkan token dari kode otorisasi
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (!isset($token['error'])) {
            // Set access token
            $client->setAccessToken($token);
            
            // Get user info
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();
            
            // Ekstrak data user dari Google
            $google_id = $google_account_info->getId();
            $email = $google_account_info->getEmail();
            $name = $google_account_info->getName();
            
            // Periksa apakah email sudah diverifikasi oleh Google
            if (!$google_account_info->verifiedEmail) {
                $_SESSION['flash_message'] = 'Email Google Anda belum diverifikasi!';
                $_SESSION['flash_type'] = 'red';
                
                // Tambahkan script untuk popup
                echo '<script>
                    if (window.opener && !window.opener.closed) {
                        window.opener.location.href = "login.php";
                        window.close();
                    } else {
                        window.location.href = "login.php";
                    }
                </script>';
                exit;
            }
            
            // Connect to database
            $db = new Database();
            $conn = $db->getConnection();
            
            if (!$conn) {
                throw new Exception("Koneksi database gagal");
            }
            
            // Cek apakah user dengan google_id ini sudah ada
            $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = :google_id");
            $stmt->bindParam(':google_id', $google_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // User sudah pernah login dengan Google, ambil data user
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Cek apakah akun aktif
                if ($user['status'] == 'nonaktif') {
                    $_SESSION['flash_message'] = 'Akun Anda dinonaktifkan, silakan hubungi admin!';
                    $_SESSION['flash_type'] = 'red';
                    
                    // Tambahkan script untuk popup
                    echo '<script>
                        if (window.opener && !window.opener.closed) {
                            window.opener.location.href = "login.php";
                            window.close();
                        } else {
                            window.location.href = "login.php";
                        }
                    </script>';
                    exit;
                }
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_email'] = $user['email'];
                
                // Tambahkan script untuk popup
                echo '<script>
                    if (window.opener && !window.opener.closed) {
                        window.opener.location.href = "' . USER_URL . '";
                        window.close();
                    } else {
                        window.location.href = "' . USER_URL . '";
                    }
                </script>';
                exit;
            } else {
                // Cek apakah email sudah terdaftar di database
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Email sudah terdaftar, update google_id ke akun yang ada
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Cek apakah akun aktif
                    if ($user['status'] == 'nonaktif') {
                        $_SESSION['flash_message'] = 'Akun Anda dinonaktifkan, silakan hubungi admin!';
                        $_SESSION['flash_type'] = 'red';
                        
                        // Tambahkan script untuk popup
                        echo '<script>
                            if (window.opener && !window.opener.closed) {
                                window.opener.location.href = "login.php";
                                window.close();
                            } else {
                                window.location.href = "login.php";
                            }
                        </script>';
                        exit;
                    }
                    
                    // Update google_id
                    $updateStmt = $conn->prepare("UPDATE users SET google_id = :google_id WHERE id = :id");
                    $updateStmt->bindParam(':google_id', $google_id);
                    $updateStmt->bindParam(':id', $user['id']);
                    $updateStmt->execute();
                    
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_nama'] = $user['nama'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Tambahkan script untuk popup
                    echo '<script>
                        if (window.opener && !window.opener.closed) {
                            window.opener.location.href = "' . USER_URL . '";
                            window.close();
                        } else {
                            window.location.href = "' . USER_URL . '";
                        }
                    </script>';
                    exit;
                } else {
                    // Buat akun baru dengan data dari Google
                    // Generate username unik dari nama
                    $username = createUniqueUsername($name);
                    
                    // Generate password acak
                    $password = generateRandomString(12);
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Buat query untuk insert user baru
                    $insertStmt = $conn->prepare("
                        INSERT INTO users (username, password, nama, email, no_telp, alamat, no_ktp, google_id, status, role) 
                        VALUES (:username, :password, :nama, :email, '-', '-', '-', :google_id, 'aktif', 'user')
                    ");
                    $insertStmt->bindParam(':username', $username);
                    $insertStmt->bindParam(':password', $hashedPassword);
                    $insertStmt->bindParam(':nama', $name);
                    $insertStmt->bindParam(':email', $email);
                    $insertStmt->bindParam(':google_id', $google_id);
                    $insertStmt->execute();
                    
                    $userId = $conn->lastInsertId();
                    
                    // Set session
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_username'] = $username;
                    $_SESSION['user_nama'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    // Tambahkan notifikasi untuk melengkapi profil
                    $notifStmt = $conn->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, status) 
                        VALUES (:user_id, 'Selamat Datang!', 'Silakan lengkapi profil Anda untuk pengalaman yang lebih baik.', 'user_baru', 'belum_dibaca')
                    ");
                    $notifStmt->bindParam(':user_id', $userId);
                    $notifStmt->execute();
                    
                    // Set flash message
                    $_SESSION['flash_message'] = 'Selamat datang! Silakan lengkapi profil Anda, terutama nomor KTP, untuk pengalaman yang lebih baik.';
                    $_SESSION['flash_type'] = 'green';
                    
                    // Tambahkan script untuk popup
                    echo '<script>
                        if (window.opener && !window.opener.closed) {
                            window.opener.location.href = "profil.php";
                            window.close();
                        } else {
                            window.location.href = "profil.php";
                        }
                    </script>';
                    exit;
                }
            }
        } else {
            throw new Exception("Error saat mendapatkan token: " . $token['error']);
        }
    } else {
        // Jika tidak ada kode otorisasi, redirect ke halaman login
        $_SESSION['flash_message'] = 'Terjadi kesalahan saat login dengan Google.';
        $_SESSION['flash_type'] = 'red';
        
        // Tambahkan script untuk popup
        echo '<script>
            if (window.opener && !window.opener.closed) {
                window.opener.location.href = "login.php";
                window.close();
            } else {
                window.location.href = "login.php";
            }
        </script>';
        exit;
    }
} catch (Exception $e) {
    error_log("Google OAuth error: " . $e->getMessage());
    $_SESSION['flash_message'] = 'Terjadi kesalahan saat login dengan Google. Silakan coba lagi.';
    $_SESSION['flash_type'] = 'red';
    
    // Tambahkan script untuk popup
    echo '<script>
        if (window.opener && !window.opener.closed) {
            window.opener.location.href = "login.php";
            window.close();
        } else {
            window.location.href = "login.php";
        }
    </script>';
    exit;
} 