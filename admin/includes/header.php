<?php
// File ini harus di-include setelah auth_check.php

// Aktifkan output buffering jika belum aktif
if (ob_get_level() == 0) ob_start();

// Jika config belum di-load
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
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
        }
        .sidebar {
            min-height: calc(100vh - 4rem);
        }
        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid white;
        }
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex flex-col min-h-screen">
        <!-- Navbar -->
        <nav class="bg-primary-700 text-white shadow-md">
            <div class="mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="p-2 rounded-md lg:hidden mr-2">
                            <i class="fas fa-bars"></i>
                        </button>
                        <a href="<?= ADMIN_URL ?>" class="text-xl font-bold flex items-center space-x-2">
                            <i class="fas fa-car-side"></i>
                            <span>Rental Mobil Admin</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="hidden md:flex items-center space-x-2">
                            <span class="text-sm"><?= $_SESSION['admin_nama'] ?? 'Admin' ?></span>
                            <span class="bg-primary-800 text-xs px-2 py-1 rounded-full">Admin</span>
                        </div>
                        <a href="<?= ADMIN_URL ?>logout.php" class="text-sm bg-primary-800 hover:bg-primary-900 px-3 py-2 rounded transition duration-200 flex items-center">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex flex-1">
            <!-- Sidebar -->
            <div id="sidebar" class="bg-gray-800 text-white w-64 py-4 shadow-lg sidebar transition-all duration-300 ease-in-out fixed lg:relative inset-y-0 left-0 transform lg:translate-x-0 -translate-x-full z-40 lg:z-auto">
                <div class="px-4 py-2">
                    <div class="mb-6 flex justify-center">
                        <div class="h-20 w-20 rounded-full bg-primary-700 flex items-center justify-center text-3xl">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="text-center mb-6">
                        <p class="text-sm text-gray-300">Selamat datang,</p>
                        <p class="font-semibold"><?= $_SESSION['admin_nama'] ?? 'Admin' ?></p>
                    </div>
                    <ul class="mt-6 space-y-1">
                        <?php
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
                        <li class="nav-item <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 rounded transition pl-3">
                                <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu === 'mobil' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>mobil/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 rounded transition pl-3">
                                <i class="fas fa-car mr-3 w-5 text-center"></i> Manajemen Mobil
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu === 'kategori' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>kategori/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 rounded transition pl-3">
                                <i class="fas fa-tags mr-3 w-5 text-center"></i> Kategori Mobil
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu === 'pemesanan' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>pemesanan/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 rounded transition pl-3">
                                <i class="fas fa-clipboard-list mr-3 w-5 text-center"></i> Pemesanan
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu === 'pengembalian' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>pengembalian/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 rounded transition pl-3">
                                <i class="fas fa-undo mr-3 w-5 text-center"></i> Pengembalian
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu === 'user' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>user/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 rounded transition pl-3">
                                <i class="fas fa-users mr-3 w-5 text-center"></i> Manajemen User
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu === 'laporan' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>laporan/index.php" class="flex items-center px-4 py-3 hover:bg-gray-700 rounded transition pl-3">
                                <i class="fas fa-chart-bar mr-3 w-5 text-center"></i> Laporan
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu === 'profile' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>profile.php" class="flex items-center px-4 py-3 hover:bg-gray-700 rounded transition pl-3">
                                <i class="fas fa-user-cog mr-3 w-5 text-center"></i> Profil
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 p-6 overflow-y-auto pl-0 lg:pl-6 w-full">
                <!-- Overlay untuk mobile sidebar -->
                <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-50 z-30 hidden lg:hidden"></div>

                <?php if(isset($_SESSION['flash_message'])): ?>
                <div class="bg-<?= $_SESSION['flash_type'] ?? 'green' ?>-100 border border-<?= $_SESSION['flash_type'] ?? 'green' ?>-400 text-<?= $_SESSION['flash_type'] ?? 'green' ?>-700 px-4 py-3 rounded-lg relative mb-4 shadow-sm" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['flash_message'] ?></span>
                    <button class="absolute top-0 bottom-0 right-0 px-4 py-3 alert-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?> 