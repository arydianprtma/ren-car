    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-auto">
        <div class="container mx-auto px-6">
            <div class="flex flex-wrap justify-between">
                <div class="w-full md:w-1/4 mb-6 md:mb-0">
                    <h3 class="text-xl font-bold mb-4">Rental Mobil</h3>
                    <p class="text-gray-300 leading-loose">
                        Kami menyediakan layanan rental mobil terbaik dengan harga terjangkau dan kualitas prima.
                    </p>
                </div>
                <div class="w-full md:w-1/4 mb-6 md:mb-0">
                    <h3 class="text-xl font-bold mb-4">Tautan</h3>
                    <ul class="text-gray-300">
                        <li class="mb-2"><a href="<?= USER_URL ?>" class="hover:text-blue-300">Beranda</a></li>
                        <li class="mb-2"><a href="<?= USER_URL ?>mobil.php" class="hover:text-blue-300">Mobil</a></li>
                        <li class="mb-2"><a href="<?= USER_URL ?>tentang.php" class="hover:text-blue-300">Tentang Kami</a></li>
                        <li class="mb-2"><a href="<?= USER_URL ?>kontak.php" class="hover:text-blue-300">Kontak</a></li>
                    </ul>
                </div>
                <div class="w-full md:w-1/4 mb-6 md:mb-0">
                    <h3 class="text-xl font-bold mb-4">Hubungi Kami</h3>
                    <ul class="text-gray-300">
                        <li class="mb-2"><i class="fas fa-map-marker-alt mr-2"></i> Jl. Contoh No. 123, Jakarta</li>
                        <li class="mb-2"><i class="fas fa-phone mr-2"></i> +62 123-4567-8901</li>
                        <li class="mb-2"><i class="fas fa-envelope mr-2"></i> info@rentalmobil.com</li>
                    </ul>
                </div>
                <div class="w-full md:w-1/4">
                    <h3 class="text-xl font-bold mb-4">Ikuti Kami</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-blue-300"><i class="fab fa-facebook-f text-xl"></i></a>
                        <a href="#" class="text-gray-300 hover:text-blue-300"><i class="fab fa-twitter text-xl"></i></a>
                        <a href="#" class="text-gray-300 hover:text-blue-300"><i class="fab fa-instagram text-xl"></i></a>
                        <a href="#" class="text-gray-300 hover:text-blue-300"><i class="fab fa-youtube text-xl"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-300">
                <p>&copy; <?= date('Y') ?> Rental Mobil - Sistem Terdistribusi. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Auto-hide flash messages after 3 seconds
        setTimeout(function() {
            const alert = document.querySelector('[role="alert"]');
            if (alert) {
                alert.style.transition = 'opacity 1s';
                alert.style.opacity = 0;
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }
        }, 3000);
    </script>
</body>
</html> 