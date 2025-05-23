<?php
// File ini harus di-include setelah auth_check.php

// Aktifkan output buffering jika belum aktif
if (ob_get_level() == 0) ob_start();

// Jika config belum di-load
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Cek jika ada notifikasi baru
require_once __DIR__ . '/../../classes/Notification.php';
// Class Database ada di config/database.php, bukan di classes/Database.php
require_once __DIR__ . '/../../config/database.php';

// Inisialisasi variabel unreadCount
$unreadCount = 0;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Pastikan class Notification tersedia
    if (!class_exists('Notification')) {
        throw new Exception("Class Notification tidak ditemukan");
    }
    
    $notif = new Notification($conn);
    
    // Pastikan metode tersedia dan admin sudah login
    if (method_exists($notif, 'getUnreadAdminNotificationsCount') && isset($_SESSION['admin_id'])) {
        $unreadCount = $notif->getUnreadAdminNotificationsCount();
    }
} catch (Exception $e) {
    // Log error dan tetapkan unreadCount ke 0
    error_log("Error initializing notification system: " . $e->getMessage());
    $unreadCount = 0;
}

// Dapatkan path saat ini
$current_path = $_SERVER['PHP_SELF'];
$active_menu = '';

// Tentukan menu aktif berdasarkan path
if (strpos($current_path, '/index.php') !== false && strpos($current_path, '/mobil/') === false && 
    strpos($current_path, '/kategori/') === false && strpos($current_path, '/pemesanan/') === false && 
    strpos($current_path, '/pengembalian/') === false && strpos($current_path, '/user/') === false && 
    strpos($current_path, '/laporan/') === false && strpos($current_path, '/profile.php') === false) {
    $active_menu = 'dashboard';
} elseif (strpos($current_path, '/mobil/') !== false) {
    $active_menu = 'mobil';
} elseif (strpos($current_path, '/kategori/') !== false) {
    $active_menu = 'kategori';
} elseif (strpos($current_path, '/pemesanan/') !== false) {
    $active_menu = 'pemesanan';
} elseif (strpos($current_path, '/pengembalian/') !== false) {
    $active_menu = 'pengembalian';
} elseif (strpos($current_path, '/user/') !== false) {
    $active_menu = 'user';
} elseif (strpos($current_path, '/laporan/') !== false) {
    $active_menu = 'laporan';
} elseif (strpos($current_path, '/profile.php') !== false) {
    $active_menu = 'profile';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Rental Mobil</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }
        
        /* Custom styles for modern admin look */
        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .main-sidebar {
            background-color: #0f1a2e;
            width: 280px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .main-content {
            margin-left: 280px;
            transition: all 0.3s ease;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 0.25rem;
            margin: 0.25rem 0.75rem;
        }
        
        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            color: white;
            background: linear-gradient(90deg, #1a56db, #3b82f6);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .nav-icon {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .menu-header {
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 1.25rem 1.5rem 0.5rem 1.5rem;
            letter-spacing: 0.05em;
        }

        .navbar-main {
            background: linear-gradient(90deg, #0c4a6e, #0ea5e9);
            position: fixed;
            top: 0;
            right: 0;
            left: 280px;
            z-index: 50;
            height: 60px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .content-wrapper {
            padding-top: 60px;
            min-height: 100vh;
        }
        
        .stats-card {
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand-icon {
            font-size: 1.5rem;
            margin-right: 0.75rem;
            color: white;
        }
        
        .sidebar-brand-text {
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .user-panel {
            margin: 1rem 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .user-panel-img {
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin-right: 0.75rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-panel-info {
            color: white;
        }
        
        .user-panel-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .user-panel-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 992px) {
            .main-sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            
            .main-sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .navbar-main {
                left: 0;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 99;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        /* Smaller mobile devices */
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 60px 0.5rem 0.5rem 0.5rem;
            }
            
            .user-panel-img {
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }
            
            .user-panel-name {
                font-size: 0.8rem;
            }
            
            .user-panel-role {
                font-size: 0.7rem;
            }
            
            .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
            
            .navbar-main {
                height: 56px;
            }
            
            .content-wrapper {
                padding-top: 56px;
            }
        }
        
        /* Animasi untuk flash message dan alerts */
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        .fade-out {
            animation: fadeOut 1s forwards;
            animation-delay: 2s;
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div id="sidebar-overlay" class="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <aside class="main-sidebar">
        <!-- Brand Logo -->
        <div class="sidebar-brand">
            <i class="fas fa-car-side sidebar-brand-icon"></i>
            <span class="sidebar-brand-text">Rental Mobil</span>
        </div>
        
        <!-- User Panel -->
        <div class="user-panel">
            <div class="user-panel-img">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-panel-info">
                <div class="user-panel-name"><?= $_SESSION['admin_nama'] ?? 'Admin' ?></div>
                <div class="user-panel-role">Administrator</div>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <!-- Menu Utama -->
            <div class="menu-header">Menu Utama</div>
            <ul class="nav-menu">
                <li>
                    <a href="<?= ADMIN_URL ?>index.php" class="nav-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
                        <i class="fas fa-gauge-high nav-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= ADMIN_URL ?>mobil/index.php" class="nav-link <?= $active_menu === 'mobil' ? 'active' : '' ?>">
                        <i class="fas fa-car nav-icon"></i>
                        <span>Manajemen Mobil</span>
                    </a>
                </li>
                <li>
                    <a href="<?= ADMIN_URL ?>kategori/index.php" class="nav-link <?= $active_menu === 'kategori' ? 'active' : '' ?>">
                        <i class="fas fa-tags nav-icon"></i>
                        <span>Kategori Mobil</span>
                    </a>
                </li>
                <li>
                    <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="nav-link <?= $active_menu === 'pemesanan' ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-list nav-icon"></i>
                        <span>Pemesanan</span>
                        <?php if ($unreadCount > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <!-- Manajemen -->
            <div class="menu-header">Manajemen</div>
            <ul class="nav-menu">
                <li>
                    <a href="<?= ADMIN_URL ?>pengembalian/index.php" class="nav-link <?= $active_menu === 'pengembalian' ? 'active' : '' ?>">
                        <i class="fas fa-undo nav-icon"></i>
                        <span>Pengembalian</span>
                    </a>
                </li>
                <li>
                    <a href="<?= ADMIN_URL ?>user/index.php" class="nav-link <?= $active_menu === 'user' ? 'active' : '' ?>">
                        <i class="fas fa-users nav-icon"></i>
                        <span>Manajemen User</span>
                    </a>
                </li>
                <li>
                    <a href="<?= ADMIN_URL ?>laporan/index.php" class="nav-link <?= $active_menu === 'laporan' ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        <span>Laporan</span>
                    </a>
                </li>
            </ul>
            
            <!-- PENGATURAN -->
            <div class="menu-header">PENGATURAN</div>
            <ul class="nav-menu">
                <li>
                    <a href="<?= ADMIN_URL ?>profile.php" class="nav-link <?= $active_menu === 'profile' ? 'active' : '' ?>">
                        <i class="fas fa-user-cog nav-icon"></i>
                        <span>Profil Admin</span>
                    </a>
                </li>
                <li>
                    <a href="<?= ADMIN_URL ?>logout.php" class="nav-link text-red-500">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
            
            <!-- Footer sidebar -->
            <div class="mt-8 px-4 py-6 text-center text-xs text-gray-400">
                <p>Rental Mobil &copy; <?= date('Y') ?></p>
            </div>
        </nav>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar-main text-white flex items-center px-4">
            <!-- Left navbar links -->
            <div class="flex items-center">
                <button id="sidebar-toggle" class="p-2 rounded-md mr-4 lg:hidden hover:bg-blue-700 transition-colors">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Right navbar links -->
            <div class="ml-auto flex items-center space-x-4">
                <div class="relative" id="notification-dropdown">
                    <a href="#" class="p-2 text-white hover:bg-blue-700 rounded-full relative transition-colors" 
                       data-dropdown-toggle="notification-dropdown-menu" aria-expanded="false">
                        <i class="fas fa-bell text-lg"></i>
                        <?php if ($unreadCount > 0): ?>
                        <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 rounded-full text-xs flex items-center justify-center notification-badge">
                            <?= $unreadCount ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <div id="notification-dropdown-menu" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-md shadow-lg z-50 transition-all duration-300">
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Notifikasi</h3>
                                <?php if ($unreadCount > 0): ?>
                                <a href="<?= ADMIN_URL ?>notifications.php?action=mark_all_read" class="text-blue-600 hover:text-blue-800 text-xs" id="mark-all-read-btn">Tandai Semua Dibaca</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-2 border-b border-gray-100 overflow-x-auto">
                            <div class="flex space-x-2 pb-1">
                                <button class="category-filter active px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200" data-category="all">Semua</button>
                                <button class="category-filter px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200" data-category="user_baru">User Baru</button>
                                <button class="category-filter px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200" data-category="pesanan_baru">Pemesanan</button>
                                <button class="category-filter px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200" data-category="pembayaran">Pembayaran</button>
                                <button class="category-filter px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200" data-category="pengembalian">Pengembalian</button>
                            </div>
                        </div>
                        <div id="notification-list" class="max-h-96 overflow-y-auto p-2">
                            <div class="text-center py-4 text-gray-500">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Memuat notifikasi...</p>
                            </div>
                        </div>
                        <div class="p-3 border-t border-gray-200 text-center">
                            <a href="<?= ADMIN_URL ?>notifications.php" class="text-blue-600 hover:text-blue-800 text-sm">Lihat Semua Notifikasi</a>
                        </div>
                    </div>
                </div>
                <div class="relative" id="profile-dropdown">
                    <div class="flex items-center space-x-2">
                        <button type="button" class="flex items-center space-x-2 cursor-pointer" 
                                data-dropdown-toggle="profile-dropdown-menu" aria-expanded="false">
                            <span class="text-sm font-medium text-white"><?= $_SESSION['admin_nama'] ?? 'Administrator' ?></span>
                            <span class="bg-blue-800 text-xs px-2 py-1 rounded-full text-white">Admin</span>
                            <i class="fas fa-chevron-down text-xs text-white"></i>
                        </button>
                    </div>
                    <div id="profile-dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 transition-all duration-300">
                        <div class="py-1">
                            <a href="<?= ADMIN_URL ?>profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-circle mr-2"></i> Profil
                            </a>
                            <a href="<?= ADMIN_URL ?>logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="container-fluid px-4 py-4">
                <!-- Flash Messages -->
                <?php if(isset($_SESSION['flash_message'])): ?>
                <div class="bg-<?= $_SESSION['flash_type'] ?? 'green' ?>-100 border border-<?= $_SESSION['flash_type'] ?? 'green' ?>-400 text-<?= $_SESSION['flash_type'] ?? 'green' ?>-700 px-4 py-3 rounded-lg relative mb-4 shadow-sm fade-out" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['flash_message'] ?></span>
                    <button class="absolute top-0 bottom-0 right-0 px-4 py-3 alert-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                
                <!-- Main Content Container -->
                <div class="main-container">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle untuk mobile
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.main-sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        // Toggle sidebar di mobile
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
                document.body.classList.toggle('overflow-hidden');
            });
        }
        
        // Tutup sidebar saat klik overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                document.body.classList.remove('overflow-hidden');
            });
        }
        
        // Auto-hide flash messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.fade-out');
            alerts.forEach(function(alert) {
                alert.addEventListener('animationend', function() {
                    alert.style.display = 'none';
                });
            });
        }, 3000);
        
        // Close alert on click
        const closeButtons = document.querySelectorAll('.alert-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const alert = this.closest('[role="alert"]');
                alert.style.display = 'none';
            });
        });
        
        // Handle resize - close sidebar on desktop view
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                document.body.classList.remove('overflow-hidden');
            }
        });
        
        // === DROPDOWN MENUS ===
        // Fungsi untuk mengelola dropdown
        function setupDropdownToggle() {
            const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');
            
            // Tutup semua dropdown saat klik di luar
            document.addEventListener('click', function(event) {
                const isDropdownButton = event.target.closest('[data-dropdown-toggle]');
                if (!isDropdownButton) {
                    // Jika klik di luar dropdown button, tutup semua dropdown
                    const dropdowns = document.querySelectorAll('[id$="-dropdown-menu"]');
                    dropdowns.forEach(dropdown => {
                        dropdown.classList.add('hidden');
                        
                        // Cari toggle button terkait
                        const toggleId = dropdown.id.replace('-menu', '');
                        const toggle = document.querySelector(`[data-dropdown-toggle="${dropdown.id}"]`);
                        if (toggle) {
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            });
            
            // Untuk setiap toggle button
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('data-dropdown-toggle');
                    const target = document.getElementById(targetId);
                    
                    if (!target) return;
                    
                    // Toggle dropdown
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    
                    // Tutup semua dropdown lainnya terlebih dahulu
                    const otherDropdowns = document.querySelectorAll('[id$="-dropdown-menu"]:not([id="' + targetId + '"])');
                    otherDropdowns.forEach(dropdown => {
                        dropdown.classList.add('hidden');
                        const otherToggle = document.querySelector(`[data-dropdown-toggle="${dropdown.id}"]`);
                        if (otherToggle) {
                            otherToggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                    
                    // Toggle dropdown yang diklik
                    if (isExpanded) {
                        target.classList.add('hidden');
                        this.setAttribute('aria-expanded', 'false');
                    } else {
                        target.classList.remove('hidden');
                        this.setAttribute('aria-expanded', 'true');
                        
                        // Jika ini dropdown notifikasi, muat data notifikasi
                        if (targetId === 'notification-dropdown-menu') {
                            loadNotifications();
                        }
                    }
                });
            });
        }
        
        // Setup dropdown menu
        setupDropdownToggle();
        
        // Fungsi untuk memuat notifikasi
        function loadNotifications() {
            const notificationList = document.getElementById('notification-list');
            if (!notificationList) return;
            
            // Tampilkan loading
            notificationList.innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Memuat notifikasi...</p>
                </div>
            `;
            
            // Ambil data notifikasi dari server
            fetch('<?= ADMIN_URL ?>ajax/get_notifications.php?limit=10')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (data.notifications.length === 0) {
                            notificationList.innerHTML = `
                                <div class="text-center py-4 text-gray-500">
                                    <i class="fas fa-bell-slash text-4xl mb-2"></i>
                                    <p>Tidak ada notifikasi</p>
                                </div>
                            `;
                        } else {
                            // Update badge count pada kategori
                            updateCategoryBadges(data.categories);
                            
                            // Dapatkan kategori aktif saat ini
                            const activeCategory = document.querySelector('.category-filter.active').dataset.category;
                            
                            // Filter notifikasi berdasarkan kategori
                            let filteredNotifications = data.notifications;
                            if (activeCategory !== 'all') {
                                filteredNotifications = data.notifications.filter(notification => 
                                    notification.tipe === activeCategory);
                            }
                            
                            if (filteredNotifications.length === 0) {
                                notificationList.innerHTML = `
                                    <div class="text-center py-4 text-gray-500">
                                        <p>Tidak ada notifikasi di kategori ini</p>
                                    </div>
                                `;
                                return;
                            }
                            
                            let html = '';
                            filteredNotifications.forEach(notification => {
                                // Tentukan icon berdasarkan tipe notifikasi
                                let iconClass = 'text-blue-500';
                                let icon = 'bell';
                                
                                switch (notification.tipe) {
                                    case 'pesanan_baru':
                                        icon = 'shopping-cart';
                                        iconClass = 'text-green-500';
                                        break;
                                    case 'pembayaran':
                                        icon = 'credit-card';
                                        iconClass = 'text-purple-500';
                                        break;
                                    case 'pengembalian':
                                        icon = 'undo';
                                        iconClass = 'text-orange-500';
                                        break;
                                    case 'user_baru':
                                        icon = 'user-plus';
                                        iconClass = 'text-indigo-500';
                                        break;
                                    case 'sistem':
                                        icon = 'cog';
                                        iconClass = 'text-gray-500';
                                        break;
                                }
                                
                                // Buat link jika ada referensi
                                let actionLink = '';
                                if (notification.referensi_id && notification.referensi_tabel) {
                                    let linkText = "";
                                    let linkUrl = "#";
                                    
                                    switch (notification.referensi_tabel) {
                                        case 'pemesanan':
                                            linkText = "Lihat Pemesanan";
                                            linkUrl = "<?= ADMIN_URL ?>pemesanan/detail.php?id=" + notification.referensi_id;
                                            break;
                                        case 'users':
                                            linkText = "Lihat User";
                                            linkUrl = "<?= ADMIN_URL ?>user/detail.php?id=" + notification.referensi_id;
                                            break;
                                        case 'mobil':
                                            linkText = "Lihat Mobil";
                                            linkUrl = "<?= ADMIN_URL ?>mobil/detail.php?id=" + notification.referensi_id;
                                            break;
                                    }
                                    
                                    if (linkText && linkUrl !== "#") {
                                        actionLink = `<a href="${linkUrl}" class="text-blue-600 hover:text-blue-800 text-xs mt-1 inline-block">${linkText}</a>`;
                                    }
                                }
                                
                                // Tambahkan tombol "Tandai Dibaca" untuk notifikasi yang belum dibaca
                                let markReadButton = '';
                                if (notification.status === 'belum_dibaca') {
                                    markReadButton = `
                                        <button class="mark-read-btn text-xs text-blue-600 hover:text-blue-800 ml-2" 
                                                data-id="${notification.id}">
                                            <i class="fas fa-check"></i> Tandai Dibaca
                                        </button>
                                    `;
                                }
                                
                                // Format tanggal
                                const date = new Date(notification.created_at);
                                const formattedDate = date.toLocaleDateString('id-ID', { 
                                    day: '2-digit', 
                                    month: 'short',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                                
                                // Tambahkan ke HTML
                                html += `
                                    <div class="p-3 border-b border-gray-100 ${notification.status === 'belum_dibaca' ? 'bg-blue-50' : ''} hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start">
                                            <div class="mr-3">
                                                <div class="w-8 h-8 rounded-full bg-${iconClass.split('-')[1]}-100 flex items-center justify-center">
                                                    <i class="fas fa-${icon} ${iconClass}"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <h5 class="font-medium text-sm text-gray-800">${notification.judul}</h5>
                                                    <div class="flex items-center">
                                                        <span class="text-xs text-gray-500">${formattedDate}</span>
                                                        ${markReadButton}
                                                    </div>
                                                </div>
                                                <p class="text-xs text-gray-600 mt-1">${notification.pesan}</p>
                                                ${actionLink}
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            notificationList.innerHTML = html;
                            
                            // Tambahkan event listener untuk "Mark Read" buttons
                            const markReadButtons = notificationList.querySelectorAll('.mark-read-btn');
                            markReadButtons.forEach(button => {
                                button.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    markNotificationAsRead(this.dataset.id);
                                });
                            });
                        }
                    } else {
                        notificationList.innerHTML = `
                            <div class="text-center py-4 text-red-500">
                                <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                                <p>Error: ${data.message || 'Gagal memuat notifikasi'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    notificationList.innerHTML = `
                        <div class="text-center py-4 text-red-500">
                            <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                            <p>Terjadi kesalahan saat memuat notifikasi</p>
                        </div>
                    `;
                });
        }
        
        // Fungsi untuk menandai notifikasi sebagai sudah dibaca
        function markNotificationAsRead(notificationId) {
            fetch(`<?= ADMIN_URL ?>notifications.php?action=mark_read&id=${notificationId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    // Reload notifikasi
                    loadNotifications();
                    // Juga reload badge counter
                    loadAdminNotifications();
                    return true;
                }
                throw new Error('Network response was not ok');
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }
        
        // Fungsi untuk memperbarui badge count pada kategori
        function updateCategoryBadges(categories) {
            const categoryFilters = document.querySelectorAll('.category-filter');
            
            categoryFilters.forEach(filter => {
                const category = filter.dataset.category;
                const badge = filter.querySelector('.badge');
                
                if (category === 'all') {
                    const totalCount = Object.values(categories).reduce((sum, count) => sum + count, 0);
                    if (totalCount > 0) {
                        if (badge) {
                            badge.textContent = totalCount;
                        } else {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'badge ml-1 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full';
                            newBadge.textContent = totalCount;
                            filter.appendChild(newBadge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                } else {
                    const count = categories[category] || 0;
                    
                    if (count > 0) {
                        if (badge) {
                            badge.textContent = count;
                        } else {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'badge ml-1 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full';
                            newBadge.textContent = count;
                            filter.appendChild(newBadge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }
            });
        }
        
        // Fungsi untuk memuat notifikasi admin dan mengupdate badge
        function loadAdminNotifications() {
            const notificationIcon = document.querySelector('[data-dropdown-toggle="notification-dropdown-menu"] i.fa-bell');
            if (!notificationIcon) return;
            
            // Tambahkan penanda loading saat memuat
            notificationIcon.classList.add('fa-spin');
            
            fetch('<?= ADMIN_URL ?>ajax/get_notifications.php?unread=true')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Hentikan spin icon
                    notificationIcon.classList.remove('fa-spin');
                    
                    if (data.success && data.count > 0) {
                        // Perbarui atau tambahkan badge notifikasi
                        let badge = document.querySelector('.notification-badge');
                        if (!badge) {
                            // Buat badge baru
                            badge = document.createElement('span');
                            badge.className = 'absolute top-0 right-0 w-5 h-5 bg-red-500 rounded-full text-xs flex items-center justify-center notification-badge';
                            notificationIcon.parentNode.appendChild(badge);
                        }
                        
                        // Update jumlah notifikasi
                        badge.textContent = data.count;
                        badge.style.display = 'flex';
                        
                        // Update tombol "Tandai Semua Dibaca"
                        const markAllReadBtn = document.getElementById('mark-all-read-btn');
                        if (markAllReadBtn) {
                            markAllReadBtn.style.display = 'block';
                        }
                    } else {
                        // Hapus badge jika tidak ada notifikasi
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.style.display = 'none';
                        }
                        
                        // Sembunyikan tombol "Tandai Semua Dibaca"
                        const markAllReadBtn = document.getElementById('mark-all-read-btn');
                        if (markAllReadBtn) {
                            markAllReadBtn.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    // Hentikan spin icon jika terjadi error
                    notificationIcon.classList.remove('fa-spin');
                    console.error('Error fetching admin notifications:', error);
                });
        }
        
        // Setup event listener untuk filter kategori
        const categoryFilters = document.querySelectorAll('.category-filter');
        categoryFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                // Hapus kelas active dari semua filter
                categoryFilters.forEach(f => f.classList.remove('active', 'bg-blue-100', 'text-blue-700'));
                categoryFilters.forEach(f => f.classList.add('bg-gray-100', 'text-gray-700'));
                
                // Tambahkan kelas active ke filter yang diklik
                this.classList.add('active', 'bg-blue-100', 'text-blue-700');
                this.classList.remove('bg-gray-100', 'text-gray-700');
                
                // Muat ulang notifikasi dengan filter
                loadNotifications();
            });
        });
        
        // Setup event listener untuk tombol "Tandai Semua Dibaca"
        const markAllReadBtn = document.getElementById('mark-all-read-btn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fetch(this.href)
                    .then(response => {
                        if (response.ok) {
                            // Reload notifikasi
                            loadNotifications();
                            // Juga reload badge counter
                            loadAdminNotifications();
                            
                            // Hilangkan badge notifikasi
                            const badge = document.querySelector('.notification-badge');
                            if (badge) {
                                badge.style.display = 'none';
                            }
                            
                            // Sembunyikan tombol "Tandai Semua Dibaca"
                            this.style.display = 'none';
                            
                            return true;
                        }
                        throw new Error('Network response was not ok');
                    })
                    .catch(error => {
                        console.error('Error marking all notifications as read:', error);
                    });
            });
        }
        
        // Muat notifikasi admin saat halaman dimuat
        loadAdminNotifications();
        
        // Muat notifikasi admin setiap 15 detik
        setInterval(loadAdminNotifications, 15000);
    });
</script>
