<?php
require_once '../config/config.php';
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-12">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-2xl font-bold mb-4">Debug Menu Dropdown</h1>
        
        <div class="flex flex-wrap gap-4 mb-8">
            <!-- User Dropdown Test -->
            <div class="relative dropdown">
                <button id="testUserDropdown" class="flex items-center bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors" data-dropdown-toggle="testUserMenu">
                    <i class="fas fa-user-circle mr-2"></i>
                    <span>User Dropdown</span>
                    <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="testUserMenu" class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-200">
                    <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                        <i class="fas fa-user-edit mr-2"></i> Profil Test
                    </a>
                    <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                        <i class="fas fa-cog mr-2"></i> Pengaturan Test
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="#" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-500 hover:text-white">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout Test
                    </a>
                </div>
            </div>
            
            <!-- Notification Dropdown Test -->
            <div class="relative dropdown">
                <button id="testNotifDropdown" class="flex items-center bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors" data-dropdown-toggle="testNotifMenu">
                    <i class="fas fa-bell mr-2"></i>
                    <span>Notifikasi Test</span>
                    <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="testNotifMenu" class="dropdown-menu hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-10 border border-gray-200">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700">Notifikasi Test</h3>
                    </div>
                    <div class="p-4 max-h-60 overflow-y-auto">
                        <div class="mb-3 pb-3 border-b border-gray-100">
                            <h4 class="text-sm font-medium text-gray-900">Notifikasi Test 1</h4>
                            <p class="text-xs text-gray-600 mt-1">Ini adalah notifikasi test pertama</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Notifikasi Test 2</h4>
                            <p class="text-xs text-gray-600 mt-1">Ini adalah notifikasi test kedua</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Log Area -->
        <div id="debugLog" class="bg-gray-100 p-4 rounded-lg text-sm font-mono h-40 overflow-auto">
            <div class="text-gray-500">Log debug akan muncul di sini...</div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Status User</h2>
        
        <div class="mb-4">
            <p><strong>User Login:</strong> <?= isLoggedIn() ? 'Ya' : 'Tidak' ?></p>
            <?php if(isLoggedIn()): ?>
            <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?></p>
            <p><strong>Username:</strong> <?= $_SESSION['user_username'] ?></p>
            <p><strong>Nama:</strong> <?= $_SESSION['user_nama'] ?></p>
            <?php endif; ?>
        </div>
        
        <h2 class="text-xl font-bold mb-4">Navigasi Cepat</h2>
        <div class="flex flex-wrap gap-3">
            <a href="<?= USER_URL ?>" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Beranda</a>
            <a href="<?= USER_URL ?>profil.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Profil</a>
            <a href="<?= USER_URL ?>pesanan.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Pesanan</a>
            <a href="<?= USER_URL ?>notifikasi.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Notifikasi</a>
            <?php if(isLoggedIn()): ?>
            <a href="<?= USER_URL ?>logout.php" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Logout</a>
            <?php else: ?>
            <a href="<?= USER_URL ?>login.php" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Login</a>
            <a href="<?= USER_URL ?>register.php" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const debugLog = document.getElementById('debugLog');
    
    function log(message) {
        const timestamp = new Date().toLocaleTimeString();
        const logItem = document.createElement('div');
        logItem.textContent = `[${timestamp}] ${message}`;
        debugLog.appendChild(logItem);
        debugLog.scrollTop = debugLog.scrollHeight;
    }
    
    log('Halaman debug dimuat');
    log('Menggunakan implementasi dropdown dengan data-dropdown-toggle');
    
    // Menangani semua dropdown button berdasarkan atribut data-dropdown-toggle
    const dropdownButtons = document.querySelectorAll('[data-dropdown-toggle]');
    
    dropdownButtons.forEach(button => {
        const targetId = button.dataset.dropdownToggle;
        const target = document.getElementById(targetId);
        
        if (!target) {
            log(`Error: Target #${targetId} tidak ditemukan untuk dropdown button`);
            return;
        }
        
        log(`Dropdown button untuk #${targetId} terdaftar`);
        
        // Saat tombol dropdown diklik
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            log(`Tombol dropdown untuk #${targetId} diklik`);
            
            // Toggle dropdown menu target
            if (target.classList.contains('hidden')) {
                // Tutup semua dropdown yang terbuka
                document.querySelectorAll('.dropdown-menu:not(.hidden)').forEach(menu => {
                    if (menu !== target) {
                        menu.classList.add('hidden');
                        log(`Menu #${menu.id} disembunyikan`);
                    }
                });
                
                // Buka dropdown ini
                target.classList.remove('hidden');
                log(`Menu #${targetId} ditampilkan`);
            } else {
                // Tutup dropdown ini
                target.classList.add('hidden');
                log(`Menu #${targetId} disembunyikan`);
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
                log(`Menu #${menu.id} disembunyikan (klik di luar)`);
            }
        });
    });
    
    // Periksa status dropdown header
    const headerUserDropdown = document.getElementById('userDropdown');
    const headerUserMenu = document.getElementById('userMenu');
    
    if (headerUserDropdown) {
        log('Header user dropdown ditemukan');
        log(`data-dropdown-toggle: ${headerUserDropdown.dataset.dropdownToggle}`);
    } else {
        log('Header user dropdown tidak ditemukan');
    }
    
    if (headerUserMenu) {
        log('Header user menu ditemukan');
        
        const computedStyle = window.getComputedStyle(headerUserMenu);
        log(`CSS computed - display: ${computedStyle.display}, visibility: ${computedStyle.visibility}, z-index: ${computedStyle.zIndex}`);
        
        log(`Classes: ${headerUserMenu.className}`);
        log(`Hidden class: ${headerUserMenu.classList.contains('hidden') ? 'Ya' : 'Tidak'}`);
        log(`Posisi: position=${computedStyle.position}, top=${computedStyle.top}, right=${computedStyle.right}`);
    } else {
        log('Header user menu tidak ditemukan');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 