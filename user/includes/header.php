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
    <!-- Custom CSS untuk dropdown -->
    <link rel="stylesheet" href="<?= USER_URL ?>assets/css/style.css">
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
            visibility: hidden;
        }
        
        .dropdown-menu.show {
            display: block !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
            visibility: visible !important;
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
        
        /* Mobile menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 300px;
            height: 100vh;
            background-color: white;
            z-index: 100;
            transition: right 0.3s ease-in-out;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        .mobile-menu.show {
            right: 0;
        }
        
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
        }
        
        .mobile-menu-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        /* Mobile notification styling */
        @media (max-width: 768px) {
            .notification-dropdown {
                position: static;
            }
            
            .notification-dropdown .dropdown-menu {
                width: 100%;
                max-width: none;
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                border-radius: 0;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="mobile-menu-overlay"></div>
    
    <!-- Mobile Menu -->
    <div id="mobileMenu" class="mobile-menu">
        <div class="p-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <a href="<?= USER_URL ?>" class="flex items-center">
                    <i class="fas fa-car-side text-blue-600 text-2xl mr-2"></i>
                    <span class="text-xl font-bold text-blue-600">Rental Mobil</span>
                </a>
                <button id="closeMobileMenu" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-4">
            <nav class="flex flex-col space-y-4">
                <a href="<?= USER_URL ?>" class="flex items-center py-2 <?= isActivePage('index.php') ? 'text-blue-600 font-semibold' : 'text-gray-700' ?>">
                    <i class="fas fa-home mr-3 w-6 text-center"></i> Beranda
                </a>
                <a href="<?= USER_URL ?>mobil.php" class="flex items-center py-2 <?= isActivePage('mobil.php') ? 'text-blue-600 font-semibold' : 'text-gray-700' ?>">
                    <i class="fas fa-car mr-3 w-6 text-center"></i> Mobil
                </a>
                <a href="<?= USER_URL ?>tentang.php" class="flex items-center py-2 <?= isActivePage('tentang.php') ? 'text-blue-600 font-semibold' : 'text-gray-700' ?>">
                    <i class="fas fa-info-circle mr-3 w-6 text-center"></i> Tentang Kami
                </a>
                <a href="<?= USER_URL ?>kontak.php" class="flex items-center py-2 <?= isActivePage('kontak.php') ? 'text-blue-600 font-semibold' : 'text-gray-700' ?>">
                    <i class="fas fa-envelope mr-3 w-6 text-center"></i> Kontak
                </a>
                
                <?php if(isLoggedIn()): ?>
                <div class="border-t border-gray-200 my-2 pt-2">
                    <a href="<?= USER_URL ?>notifikasi.php" class="flex items-center py-2 text-gray-700">
                        <i class="fas fa-bell mr-3 w-6 text-center"></i> 
                        Notifikasi
                        <?php if($unreadNotificationsCount > 0): ?>
                        <span class="ml-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?= $unreadNotificationsCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= USER_URL ?>profil.php" class="flex items-center py-2 <?= isActivePage('profil.php') ? 'text-blue-600 font-semibold' : 'text-gray-700' ?>">
                        <i class="fas fa-user mr-3 w-6 text-center"></i> Profil
                    </a>
                    <a href="<?= USER_URL ?>pesanan.php" class="flex items-center py-2 <?= isActivePage('pesanan.php') ? 'text-blue-600 font-semibold' : 'text-gray-700' ?>">
                        <i class="fas fa-shopping-bag mr-3 w-6 text-center"></i> Pesanan Saya
                    </a>
                    <a href="<?= USER_URL ?>logout.php" class="flex items-center py-2 text-red-600">
                        <i class="fas fa-sign-out-alt mr-3 w-6 text-center"></i> Logout
                    </a>
                </div>
                <?php else: ?>
                <div class="border-t border-gray-200 my-2 pt-2">
                    <a href="<?= USER_URL ?>login.php" class="flex items-center py-2 <?= isActivePage('login.php') ? 'text-blue-600 font-semibold' : 'text-gray-700' ?>">
                        <i class="fas fa-sign-in-alt mr-3 w-6 text-center"></i> Login
                    </a>
                    <a href="<?= USER_URL ?>register.php" class="mt-2 flex justify-center items-center bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg transition-all duration-300">
                        <i class="fas fa-user-plus mr-2"></i> Daftar
                    </a>
                </div>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center">
                    <a href="<?= USER_URL ?>" class="flex items-center">
                        <i class="fas fa-car-side text-blue-600 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-blue-600">Rental Mobil</span>
                    </a>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="<?= USER_URL ?>" class="nav-item <?= isActivePage('index.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Beranda</a>
                    <a href="<?= USER_URL ?>mobil.php" class="nav-item <?= isActivePage('mobil.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Mobil</a>
                    <a href="<?= USER_URL ?>tentang.php" class="nav-item <?= isActivePage('tentang.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Tentang Kami</a>
                    <a href="<?= USER_URL ?>kontak.php" class="nav-item <?= isActivePage('kontak.php') ? 'active' : 'text-gray-700 hover:text-blue-500' ?> transition duration-300">Kontak</a>
                    
                    <?php if(isLoggedIn()): ?>
                        <!-- Notifikasi Dropdown -->
                        <div class="relative dropdown notification-dropdown">
                            <button id="notificationDropdown" class="text-gray-700 hover:text-blue-500 transition px-3 py-1 rounded-full border border-transparent hover:border-blue-100 hover:bg-blue-50 relative" data-dropdown-toggle="notificationMenu">
                                <i class="fas fa-bell text-blue-500"></i>
                                <?php if($unreadNotificationsCount > 0): ?>
                                <span class="notification-badge"><?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?></span>
                                <?php endif; ?>
                            </button>
                            <div id="notificationMenu" class="dropdown-menu hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-100" style="min-width: 20rem; max-width: 24rem;">
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
                            <button id="userDropdown" class="flex items-center text-gray-700 hover:text-blue-500 transition px-3 py-1 rounded-full border border-transparent hover:border-blue-100 hover:bg-blue-50" data-dropdown-toggle="userMenu">
                                <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                                <span class="mr-1"><?= $_SESSION['user_nama'] ?? 'User' ?></span>
                                <svg class="h-4 w-4 fill-current text-gray-500 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                </svg>
                            </button>
                            <div id="userMenu" class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-100" style="min-width: 12rem; max-width: 20rem;">
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
                
                <!-- Mobile Menu Button -->
                <button id="mobileMenuBtn" class="md:hidden flex items-center text-gray-700 focus:outline-none">
                    <?php if(isLoggedIn() && $unreadNotificationsCount > 0): ?>
                    <span class="relative mr-4">
                        <i class="fas fa-bell text-blue-500"></i>
                        <span class="notification-badge"><?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?></span>
                    </span>
                    <?php endif; ?>
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Flash Message -->
    <?php if($flash_message): ?>
    <div class="container mx-auto px-4 mt-4">
        <div class="bg-<?= $flash_type ?>-100 border border-<?= $flash_type ?>-400 text-<?= $flash_type ?>-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline"><?= $flash_message ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3 alert-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Implementasi dropdown menu yang baru dan lebih sederhana
        document.addEventListener('DOMContentLoaded', function() {
            // Menangani semua dropdown button berdasarkan atribut data-dropdown-toggle
            const dropdownButtons = document.querySelectorAll('[data-dropdown-toggle]');
            
            dropdownButtons.forEach(button => {
                const targetId = button.dataset.dropdownToggle;
                const target = document.getElementById(targetId);
            
                if (!target) return;
                
                // Saat tombol dropdown diklik
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle dropdown menu target
                    if (target.classList.contains('hidden')) {
                        // Tutup semua dropdown yang terbuka
                        document.querySelectorAll('.dropdown-menu:not(.hidden)').forEach(menu => {
                            if (menu !== target) menu.classList.add('hidden');
                        });
                        
                        // Buka dropdown ini
                        target.classList.remove('hidden');
                        
                        // Muat notifikasi jika ini adalah dropdown notifikasi
                        if (targetId === 'notificationMenu') {
                            loadNotifications();
                        }
                    } else {
                        // Tutup dropdown ini
                        target.classList.add('hidden');
                    }
                });
                });
                
            // Tutup dropdown saat mengklik di luar
                document.addEventListener('click', function(e) {
                const openMenus = document.querySelectorAll('.dropdown-menu:not(.hidden)');
                
                openMenus.forEach(menu => {
                    // Cek apakah klik di luar menu dan di luar tombol dropdown
                    const toggleButton = document.querySelector(`[data-dropdown-toggle="${menu.id}"]`);
                    
                    if (!menu.contains(e.target) && (!toggleButton || !toggleButton.contains(e.target))) {
                        menu.classList.add('hidden');
                    }
                });
            });
            
            // Fungsi untuk memuat notifikasi
            window.loadNotifications = function() {
                const notificationList = document.getElementById('notificationList');
                if (!notificationList) return;
                
                // Cek apakah sudah dimuat
                if (notificationList.dataset.loaded === 'true') return;
                
                fetch('<?= USER_URL ?>ajax/get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
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
                        
                        // Tandai sebagai sudah dimuat
                        notificationList.dataset.loaded = 'true';
                    })
                    .catch(error => {
                        console.error('Error fetching notifications:', error);
                        notificationList.innerHTML = '<div class="text-center py-4 text-red-500 text-sm">Gagal memuat notifikasi</div>';
                });
            }
            
            // Menutup flash message
            const closeButtons = document.querySelectorAll('[role="alert"] button');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.style.display = 'none';
                });
            });
        });
        
        // Helper functions untuk notifikasi (global scope)
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
    </script>
</body>
</html> 