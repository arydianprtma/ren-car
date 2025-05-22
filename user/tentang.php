<?php
require_once 'includes/header.php';
?>

<style>
    .bg-pattern {
        background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.2' fill-rule='evenodd'/%3E%3C/svg%3E");
    }
</style>

<!-- Hero Section -->
<section class="relative bg-gradient-to-r from-blue-700 to-blue-500 py-24">
    <div class="absolute inset-0 bg-black opacity-40"></div>
    <div class="absolute inset-0" style="background-image: url('<?= ASSETS_URL ?>images/car-login.jpg'); background-size: cover; background-position: center; mix-blend-mode: overlay; opacity: 0.4;"></div>
    <div class="container mx-auto px-6 relative z-10">
        <div class="text-center text-white">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 drop-shadow-md">Tentang Kami</h1>
            <div class="w-24 h-1 bg-white mx-auto mb-6 rounded-full"></div>
            <p class="text-xl max-w-3xl mx-auto drop-shadow-sm">Penyedia jasa rental mobil terpercaya dengan layanan terbaik untuk setiap perjalanan Anda</p>
        </div>
    </div>
</section>

<!-- Sejarah Perusahaan -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Sejarah Perusahaan</h2>
                <div class="w-20 h-1 bg-blue-600 mb-6 rounded-full"></div>
                <p class="text-gray-600 mb-4 leading-relaxed">
                    Didirikan pada tahun 2010, Rental Mobil hadir untuk memenuhi kebutuhan transportasi masyarakat Indonesia dengan menyediakan layanan sewa kendaraan yang berkualitas dan terjangkau.
                </p>
                <p class="text-gray-600 mb-4 leading-relaxed">
                    Berawal dari armada 5 kendaraan, kini kami telah berkembang menjadi salah satu penyedia layanan rental mobil terbesar dengan lebih dari 200 unit kendaraan berbagai jenis yang tersebar di berbagai kota besar di Indonesia.
                </p>
                <p class="text-gray-600 leading-relaxed">
                    Komitmen kami terhadap kualitas pelayanan dan kepuasan pelanggan menjadikan Rental Mobil sebagai pilihan utama bagi masyarakat untuk memenuhi kebutuhan transportasi mereka.
                </p>
            </div>
            <div class="flex justify-center items-center">
                <!-- Container dengan desain modern yang seimbang -->
                <div class="relative w-4/5 max-w-sm">
                    <!-- Efek bayangan dengan blur yang lebih halus tapi jelas -->
                    <div class="absolute -bottom-3 -right-3 w-full h-full bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl opacity-35 blur-sm z-0 transform rotate-2"></div>
                    
                    <!-- Element dekoratif dengan kejelasan yang ditingkatkan -->
                    <div class="absolute -top-3 -left-3 w-14 h-14 bg-gray-100 rounded-full z-0 flex items-center justify-center shadow-md">
                        <div class="w-8 h-8 bg-white rounded-full"></div>
                    </div>
                    
                    <!-- Gambar utama dengan border yang lebih jelas -->
                    <div class="relative overflow-hidden rounded-xl border border-gray-200 shadow-lg aspect-square bg-white z-10">
                        <!-- Subtle overlay yang lebih jelas -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-blue-900/15 to-transparent z-20"></div>
                        
                        <img src="<?= ASSETS_URL ?>images/about-company.jpg" alt="Sejarah Perusahaan" 
                             class="w-full h-full object-cover object-center" 
                             onerror="this.src='<?= ASSETS_URL ?>images/car-login.jpg'">
                    </div>
                    
                    <!-- Badge dengan efek blur yang dikontrol -->
                    <div class="absolute -bottom-3 right-6 bg-blue-600 bg-opacity-90 backdrop-blur-sm text-white py-1.5 px-4 text-sm font-semibold rounded-full shadow-md z-20 border border-blue-500">
                        <span class="inline-block mr-1.5 w-1.5 h-1.5 bg-white rounded-full"></span> Sejak 2010
                    </div>
                </div>
            </div>
        </div>
    </div>
</section> 

<!-- Visi & Misi -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Visi & Misi</h2>
            <div class="w-20 h-1 bg-blue-600 mx-auto mb-6 rounded-full"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">Komitmen kami untuk memberikan layanan terbaik bagi pelanggan</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl shadow-md p-8 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-blue-100 rounded-full p-4 inline-flex w-16 h-16 items-center justify-center mb-6">
                    <i class="fas fa-eye text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Visi</h3>
                <p class="text-gray-600 leading-relaxed">
                    Menjadi penyedia jasa rental mobil terkemuka di Indonesia dengan standar pelayanan internasional dan armada terlengkap untuk mendukung mobilitas masyarakat Indonesia.
                </p>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-8 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-blue-100 rounded-full p-4 inline-flex w-16 h-16 items-center justify-center mb-6">
                    <i class="fas fa-bullseye text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Misi</h3>
                <ul class="text-gray-600 leading-relaxed space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                        <span>Menyediakan armada kendaraan yang berkualitas dan terawat</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                        <span>Memberikan pelayanan prima dengan didukung oleh tim profesional</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                        <span>Menjaga standar keamanan dan kenyamanan tertinggi bagi pelanggan</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                        <span>Mengembangkan jaringan layanan di seluruh kota besar di Indonesia</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                        <span>Berkontribusi positif terhadap masyarakat dan lingkungan</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Tim Kami -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Tim Kami</h2>
            <div class="w-20 h-1 bg-blue-600 mx-auto mb-6 rounded-full"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">Dikelola oleh profesional berpengalaman di bidangnya</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-28 h-28 rounded-full overflow-hidden mx-auto mb-6 border-4 border-blue-100 shadow-md">
                    <img src="<?= ASSETS_URL ?>images/team/team1.jpg" alt="CEO" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=Budi+Santoso&background=0062ff&color=fff&size=150'">
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Ary Dian Pratama</h3>
                <p class="text-blue-600 font-medium mb-4">CEO & Founder</p>
                <p class="text-gray-600 text-sm mb-4">Lebih dari 15 tahun pengalaman di industri otomotif dan transportasi</p>
                <div class="flex items-center justify-center space-x-3">
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-28 h-28 rounded-full overflow-hidden mx-auto mb-6 border-4 border-blue-100 shadow-md">
                    <img src="<?= ASSETS_URL ?>images/team/team2.jpg" alt="COO" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=Dewi+Lestari&background=0062ff&color=fff&size=150'">
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Yardhan Zaendhi Anargya</h3>
                <p class="text-blue-600 font-medium mb-4">Chief Operations Officer</p>
                <p class="text-gray-600 text-sm mb-4">Ahli dalam manajemen operasional dan pengembangan bisnis</p>
                <div class="flex items-center justify-center space-x-3">
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-28 h-28 rounded-full overflow-hidden mx-auto mb-6 border-4 border-blue-100 shadow-md">
                    <img src="<?= ASSETS_URL ?>images/team/team3.jpg" alt="CTO" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=Rudi+Hermawan&background=0062ff&color=fff&size=150'">
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Cooming Soon</h3>
                <p class="text-blue-600 font-medium mb-4">Chief Technology Officer</p>
                <p class="text-gray-600 text-sm mb-4">Spesialis teknologi dan sistem informasi transportasi</p>
                <div class="flex items-center justify-center space-x-3">
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 text-center hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-28 h-28 rounded-full overflow-hidden mx-auto mb-6 border-4 border-blue-100 shadow-md">
                    <img src="<?= ASSETS_URL ?>images/team/team4.jpg" alt="CFO" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=Maya+Sari&background=0062ff&color=fff&size=150'">
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Cooming Soon</h3>
                <p class="text-blue-600 font-medium mb-4">Chief Financial Officer</p>
                <p class="text-gray-600 text-sm mb-4">Berpengalaman dalam manajemen keuangan dan investasi</p>
                <div class="flex items-center justify-center space-x-3">
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistik -->
<section class="py-16 bg-gradient-to-r from-blue-700 to-blue-500 text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-pattern opacity-10"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-10 rounded-full -mt-20 -mr-20"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-white opacity-10 rounded-full -mb-20 -ml-20"></div>
    <div class="container mx-auto px-6 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center bg-white bg-opacity-10 p-6 rounded-lg backdrop-blur-sm">
                <div class="text-5xl font-bold mb-2">200<span class="text-4xl">+</span></div>
                <div class="text-lg font-medium">Kendaraan</div>
            </div>
            
            <div class="text-center bg-white bg-opacity-10 p-6 rounded-lg backdrop-blur-sm">
                <div class="text-5xl font-bold mb-2">15</div>
                <div class="text-lg font-medium">Kota</div>
            </div>
            
            <div class="text-center bg-white bg-opacity-10 p-6 rounded-lg backdrop-blur-sm">
                <div class="text-5xl font-bold mb-2">10<span class="text-4xl">k+</span></div>
                <div class="text-lg font-medium">Pelanggan Puas</div>
            </div>
            
            <div class="text-center bg-white bg-opacity-10 p-6 rounded-lg backdrop-blur-sm">
                <div class="text-5xl font-bold mb-2">13</div>
                <div class="text-lg font-medium">Tahun Pengalaman</div>
            </div>
        </div>
    </div>
</section>

<!-- Mengapa Memilih Kami -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Mengapa Memilih Kami</h2>
            <div class="w-20 h-1 bg-blue-600 mx-auto mb-6 rounded-full"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">Kami hadir untuk memberikan pengalaman rental mobil terbaik</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 hover:border-blue-100 transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-4 inline-flex w-16 h-16 items-center justify-center mb-6 shadow-md text-white">
                    <i class="fas fa-car text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Armada Berkualitas</h3>
                <p class="text-gray-600">Kendaraan baru, terawat, dan rutin diservis untuk kenyamanan dan keamanan Anda.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 hover:border-blue-100 transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-4 inline-flex w-16 h-16 items-center justify-center mb-6 shadow-md text-white">
                    <i class="fas fa-hand-holding-usd text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Harga Kompetitif</h3>
                <p class="text-gray-600">Kami menawarkan harga yang transparan dan terjangkau dengan berbagai pilihan paket.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 hover:border-blue-100 transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-4 inline-flex w-16 h-16 items-center justify-center mb-6 shadow-md text-white">
                    <i class="fas fa-headset text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Layanan 24/7</h3>
                <p class="text-gray-600">Tim customer service kami selalu siap membantu kebutuhan Anda kapan saja.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 hover:border-blue-100 transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-4 inline-flex w-16 h-16 items-center justify-center mb-6 shadow-md text-white">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Keamanan Terjamin</h3>
                <p class="text-gray-600">Semua kendaraan kami dilengkapi dengan fitur keamanan dan asuransi lengkap.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 hover:border-blue-100 transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-4 inline-flex w-16 h-16 items-center justify-center mb-6 shadow-md text-white">
                    <i class="fas fa-map-marked-alt text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Jangkauan Luas</h3>
                <p class="text-gray-600">Tersedia di berbagai kota besar di Indonesia untuk kemudahan akses.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 hover:bg-white border border-gray-100 hover:border-blue-100 transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-full p-4 inline-flex w-16 h-16 items-center justify-center mb-6 shadow-md text-white">
                    <i class="fas fa-smile text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Kepuasan Pelanggan</h3>
                <p class="text-gray-600">Kepuasan Anda adalah prioritas utama kami dalam memberikan pelayanan.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-6">
        <div class="bg-gradient-to-r from-blue-700 to-blue-500 rounded-xl p-10 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full -mt-20 -mr-20"></div>
            <div class="absolute bottom-0 left-0 w-40 h-40 bg-white opacity-10 rounded-full -mb-10 -ml-10"></div>
            <div class="relative z-10 grid grid-cols-1 md:grid-cols-7 gap-8 items-center">
                <div class="md:col-span-5 text-white">
                    <h2 class="text-3xl font-bold mb-4">Siap untuk menyewa mobil?</h2>
                    <p class="text-lg opacity-90 mb-0">Hubungi kami sekarang untuk mendapatkan penawaran terbaik.</p>
                </div>
                <div class="md:col-span-2 flex flex-col space-y-3">
                    <a href="<?= USER_URL ?>mobil.php" class="bg-white text-blue-600 font-semibold py-3 px-6 rounded-lg hover:bg-blue-50 transition duration-300 text-center shadow-md flex items-center justify-center">
                        <i class="fas fa-car-side mr-2"></i> Lihat Armada
                    </a>
                    <a href="<?= USER_URL ?>kontak.php" class="bg-transparent text-white font-semibold py-3 px-6 rounded-lg border-2 border-white hover:bg-white hover:text-blue-600 transition duration-300 text-center flex items-center justify-center">
                        <i class="fas fa-envelope mr-2"></i> Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?> 