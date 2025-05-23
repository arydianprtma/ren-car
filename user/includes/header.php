<?php
require_once '../config/config.php';

// Inisialisasi koneksi database
$database = new Database();
$conn = $database->getConnection();

// Inisialisasi notifikasi jika user sudah login
$unreadNotificationsCount = 0;
if(isLoggedIn()) {
    require_once __DIR__ . '/../../classes/Notification.php';
    if (isset($conn)) {
        $notificationObj = new Notification($conn);
        $unreadNotificationsCount = $notificationObj->countUnreadNotifications($_SESSION['user_id']);
    } else {
        // Log error jika koneksi database gagal
        error_log("Header: Koneksi database tidak tersedia");
    }
}

// Fungsi untuk mengecek halaman aktif
function isActivePage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($page === 'index.php' && $currentPage === 'index.php') {
        return true;
    } elseif ($page === $currentPage) {
        return true;
    }
    return false;
}

// Proses flash message terlebih dahulu sebelum output HTML
$flash_message = null;
$flash_type = null;
if(isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'] ?? 'green';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Output HTML di bawah ini, setelah semua proses PHP yang mungkin menggunakan header()
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Mobil - Sewa Mobil Terbaik</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Additional Styles -->
    <style>
        .hero-section {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), url('<?= ASSETS_URL ?>images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            height: 500px;
        }
        
        /* Animasi untuk menu aktif */
        .nav-item {
            position: relative;
        }
        
        .nav-item.active {
            font-weight: 600;
            color: #2563eb; /* blue-600 */
        }
        
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #2563eb; /* blue-600 */
            border-radius: 3px;
            animation: navSlideIn 0.3s ease-in-out;
        }
        
        @keyframes navSlideIn {
            from {
                width: 0;
                opacity: 0;
            }
            to {
                width: 100%;
                opacity: 1;
            }
        }
        
        /* Dropdown styling */
        .dropdown-menu {
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s, transform 0.3s;
        }
        
        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        /* Notifikasi Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444; /* red-500 */
            color: white;
            border-radius: 999px;
            font-size: 0.7rem;
            padding: 0.1rem 0.4rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="<?= USER_URL ?>" class="flex items-center">
                        <i class="fas fa-car-side text-blue-600 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-blue-600">Rental Mobil</span>
                    </a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="<?= USER_URL ?>" class="nav-item <?= isActivePage('index.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Beranda</a>
                    <a href="<?= USER_URL ?>mobil.php" class="nav-item <?= isActivePage('mobil.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Mobil</a>
                    <a href="<?= USER_URL ?>tentang.php" class="nav-item <?= isActivePage('tentang.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Tentang Kami</a>
                    <a href="<?= USER_URL ?>kontak.php" class="nav-item <?= isActivePage('kontak.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Kontak</a>
                    
                    <?php if(isLoggedIn()): ?>
                        <!-- Notifikasi Dropdown -->
                        <div class="relative dropdown">
                            <button id="notificationDropdown" class="text-gray-700 hover:text-blue-500 transition px-3 py-1 rounded-full border border-transparent hover:border-blue-100 hover:bg-blue-50 relative">
                                <i class="fas fa-bell text-blue-500"></i>
                                <?php if($unreadNotificationsCount > 0): ?>
                                <span class="notification-badge"><?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?></span>
                                <?php endif; ?>
                            </button>
                            <div id="notificationMenu" class="dropdown-menu absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-100">
                                <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                                    <h3 class="text-sm font-semibold text-gray-700">Notifikasi</h3>
                                    <?php if($unreadNotificationsCount > 0): ?>
                                    <a href="<?= USER_URL ?>notifikasi.php?action=mark_all_read" class="text-xs text-blue-500 hover:text-blue-700">Tandai semua dibaca</a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="notification-list max-h-60 overflow-y-auto p-1" id="notificationList">
                                    <!-- Notifikasi akan dimuat melalui AJAX -->
                                    <div class="text-center py-4 text-gray-500 text-sm">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Memuat notifikasi...
                                    </div>
                                </div>
                                
                                <div class="px-4 py-2 border-t border-gray-100 text-center">
                                    <a href="<?= USER_URL ?>notifikasi.php" class="text-xs text-blue-500 hover:text-blue-700">Lihat semua notifikasi</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Dropdown -->
                        <div class="relative dropdown">
                            <button id="userDropdown" class="flex items-center text-gray-700 hover:text-blue-500 transition px-3 py-1 rounded-full border border-transparent hover:border-blue-100 hover:bg-blue-50">
                                <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                                <span class="mr-1"><?= $_SESSION['user_nama'] ?? 'User' ?></span>
                                <svg class="h-4 w-4 fill-current text-gray-500 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                </svg>
                            </button>
                            <div id="userMenu" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-100">
                                <a href="<?= USER_URL ?>profil.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white <?= isActivePage('profil.php') ? 'bg-blue-50' : '' ?>">
                                    <i class="fas fa-user-edit mr-2"></i> Profil
                                </a>
                                <a href="<?= USER_URL ?>pesanan.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white <?= isActivePage('pesanan.php') ? 'bg-blue-50' : '' ?>">
                                    <i class="fas fa-shopping-bag mr-2"></i> Pesanan Saya
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="<?= USER_URL ?>logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-500 hover:text-white">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= USER_URL ?>login.php" class="nav-item <?= isActivePage('login.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Login</a>
                        <a href="<?= USER_URL ?>register.php" class="<?= isActivePage('register.php') ? 'bg-blue-600' : 'bg-blue-500 hover:bg-blue-600' ?> text-white px-5 py-2 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if($flash_message): ?>
    <div class="container mx-auto px-6 py-3">
        <div class="bg-<?= $flash_type ?>-100 border border-<?= $flash_type ?>-400 text-<?= $flash_type ?>-700 px-4 py-3 rounded-lg relative flex items-center" role="alert">
            <i class="fas fa-<?= $flash_type == 'red' ? 'exclamation-circle' : 'check-circle' ?> mr-3"></i>
            <span class="block sm:inline"><?= $flash_message ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                <svg class="fill-current h-6 w-6 text-<?= $flash_type ?>-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->

    <script>
        // Toggle dropdown untuk menu user dan notifikasi
        document.addEventListener('DOMContentLoaded', function() {
            // User dropdown
            const userDropdown = document.getElementById('userDropdown');
            const userMenu = document.getElementById('userMenu');
            
            if (userDropdown && userMenu) {
                userDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('show');
                    // Sembunyikan notifikasi menu jika terbuka
                    if (notificationMenu && notificationMenu.classList.contains('show')) {
                        notificationMenu.classList.remove('show');
                    }
                });
            }
            
            // Notifikasi dropdown
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationMenu = document.getElementById('notificationMenu');
            const notificationList = document.getElementById('notificationList');
            
            if (notificationDropdown && notificationMenu) {
                notificationDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationMenu.classList.toggle('show');
                    
                    // Sembunyikan user menu jika terbuka
                    if (userMenu && userMenu.classList.contains('show')) {
                        userMenu.classList.remove('show');
                    }
                    
                    // Load notifikasi melalui AJAX hanya jika dropdown ditampilkan
                    if (notificationMenu.classList.contains('show')) {
                        loadNotifications();
                    }
                });
            }
            
            // Tutup dropdown saat klik di luar
            document.addEventListener('click', function(e) {
                if (userMenu && userMenu.classList.contains('show') && !userMenu.contains(e.target) && e.target !== userDropdown) {
                    userMenu.classList.remove('show');
                }
                
                if (notificationMenu && notificationMenu.classList.contains('show') && !notificationMenu.contains(e.target) && e.target !== notificationDropdown) {
                    notificationMenu.classList.remove('show');
                }
            });
            
            // Fungsi untuk memuat notifikasi
            function loadNotifications() {
                fetch('<?= USER_URL ?>ajax/get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        if (notificationList) {
                            if (data.notifications.length === 0) {
                                notificationList.innerHTML = '<div class="text-center py-4 text-gray-500 text-sm">Tidak ada notifikasi</div>';
                            } else {
                                let html = '';
                                data.notifications.forEach(notification => {
                                    const isUnread = notification.status === 'belum_dibaca';
                                    html += `
                                    <a href="<?= USER_URL ?>notifikasi.php?id=${notification.id}" class="block px-4 py-2 hover:bg-gray-50 transition-colors ${isUnread ? 'bg-blue-50' : ''}">
                                        <div class="flex items-start">
                                            <div class="rounded-full p-2 ${getNotificationIconBg(notification.tipe)} mr-3">
                                                <i class="fas ${getNotificationIcon(notification.tipe)} text-white text-xs"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-center">
                                                    <h4 class="text-sm font-medium text-gray-900">${notification.judul}</h4>
                                                    <span class="text-xs text-gray-500">${formatDate(notification.created_at)}</span>
                                                </div>
                                                <p class="text-xs text-gray-600 mt-1">${notification.pesan.substring(0, 80)}${notification.pesan.length > 80 ? '...' : ''}</p>
                                            </div>
                                            ${isUnread ? '<span class="inline-block w-2 h-2 bg-blue-500 rounded-full ml-1"></span>' : ''}
                                        </div>
                                    </a>
                                    `;
                                });
                                notificationList.innerHTML = html;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching notifications:', error);
                        if (notificationList) {
                            notificationList.innerHTML = '<div class="text-center py-4 text-red-500 text-sm">Gagal memuat notifikasi</div>';
                        }
                    });
            }
            
            // Helper functions untuk notifikasi
            function getNotificationIcon(type) {
                switch(type) {
                    case 'pembayaran': return 'fa-credit-card';
                    case 'konfirmasi': return 'fa-check-circle';
                    case 'pengembalian': return 'fa-car';
                    case 'ulasan': return 'fa-star';
                    case 'umum': 
                    default: return 'fa-bell';
                }
            }
            
            function getNotificationIconBg(type) {
                switch(type) {
                    case 'pembayaran': return 'bg-blue-500';
                    case 'konfirmasi': return 'bg-green-500';
                    case 'pengembalian': return 'bg-yellow-500';
                    case 'ulasan': return 'bg-purple-500';
                    case 'umum': 
                    default: return 'bg-gray-500';
                }
            }
            
            function formatDate(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffTime = Math.abs(now - date);
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays === 0) {
                    const hours = date.getHours().toString().padStart(2, '0');
                    const minutes = date.getMinutes().toString().padStart(2, '0');
                    return `${hours}:${minutes}`;
                } else if (diffDays === 1) {
                    return 'Kemarin';
                } else {
                    const day = date.getDate().toString().padStart(2, '0');
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    return `${day}/${month}`;
                }
            }
            
            // Menutup flash message
            const closeButtons = document.querySelectorAll('[role="alert"] button');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.style.display = 'none';
                });
            });
        });
    </script>
</body>
</html> 