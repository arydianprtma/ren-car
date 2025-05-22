<!-- Footer -->
<footer class="bg-gray-800 text-white py-8 mt-auto">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-semibold mb-4 text-blue-400">Rental Mobil</h3>
                <p class="text-sm text-gray-400 mb-4">
                    Kami menyediakan layanan rental mobil terbaik dengan harga terjangkau dan armada berkualitas.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-4 text-blue-400">Layanan</h3>
                <ul class="text-sm space-y-2">
                    <li><a href="<?= USER_URL ?>mobil.php" class="text-gray-400 hover:text-white transition">Sewa Mobil</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Sewa dengan Sopir</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Sewa Jangka Panjang</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Antar Jemput</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-4 text-blue-400">Tautan</h3>
                <ul class="text-sm space-y-2">
                    <li><a href="<?= USER_URL ?>" class="text-gray-400 hover:text-white transition">Beranda</a></li>
                    <li><a href="<?= USER_URL ?>tentang.php" class="text-gray-400 hover:text-white transition">Tentang Kami</a></li>
                    <li><a href="<?= USER_URL ?>kontak.php" class="text-gray-400 hover:text-white transition">Kontak</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Syarat & Ketentuan</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-4 text-blue-400">Kontak Kami</h3>
                <ul class="text-sm space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt text-blue-400 mt-1 mr-2"></i>
                        <span class="text-gray-400">Jl. Contoh No. 123, Kota, Kode Pos 12345</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-phone text-blue-400 mt-1 mr-2"></i>
                        <span class="text-gray-400">+62 123 4567 890</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-envelope text-blue-400 mt-1 mr-2"></i>
                        <span class="text-gray-400">info@rentalmobil.com</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-700 mt-8 pt-6 text-sm text-gray-500 text-center">
            <p>&copy; <?= date('Y') ?> Rental Mobil. All rights reserved.</p>
        </div>
    </div>
</footer>
</body>
</html> 