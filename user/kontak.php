<?php
require_once 'includes/header.php';

$pesan_sukses = false;

// Proses form kontak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $subjek = $_POST['subjek'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    
    // Validasi sederhana
    $errors = [];
    if (empty($nama)) {
        $errors[] = 'Nama harus diisi';
    }
    if (empty($email)) {
        $errors[] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    if (empty($subjek)) {
        $errors[] = 'Subjek harus diisi';
    }
    if (empty($pesan)) {
        $errors[] = 'Pesan harus diisi';
    }
    
    // Jika tidak ada error, simpan pesan ke database
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "INSERT INTO pesan_kontak (nama, email, subjek, pesan, status, created_at) 
                VALUES (:nama, :email, :subjek, :pesan, 'belum_dibaca', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subjek', $subjek);
        $stmt->bindParam(':pesan', $pesan);
        
        if ($stmt->execute()) {
            $pesan_sukses = true;
            
            // Reset form
            $nama = $email = $subjek = $pesan = '';
            
            // Set flash message
            $_SESSION['flash_message'] = 'Pesan Anda berhasil dikirim! Kami akan segera menghubungi Anda.';
            $_SESSION['flash_type'] = 'green';
            
            // Redirect untuk menghindari resubmission
            header('Location: ' . USER_URL . 'kontak.php?sent=1');
            exit;
        } else {
            $errors[] = 'Terjadi kesalahan, silakan coba lagi';
        }
    }
}

// Cek apakah ada parameter sent=1 dari redirect setelah submit
$pesan_sukses = isset($_GET['sent']) && $_GET['sent'] == '1';
?>

<style>
    /* Skeleton loader dengan efek shimmer */
    .skeleton-shimmer {
        position: relative;
        overflow: hidden;
        background: #f0f0f0;
    }
    
    .skeleton-shimmer::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        transform: translateX(-100%);
        background-image: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0) 0,
            rgba(255, 255, 255, 0.2) 20%,
            rgba(255, 255, 255, 0.5) 60%,
            rgba(255, 255, 255, 0)
        );
        animation: shimmer 2s infinite;
        content: '';
    }
    
    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }
</style>

<!-- Hero Section -->
<section class="relative bg-gradient-to-r from-blue-700 to-blue-500 py-12">
    <div class="absolute inset-0 bg-black opacity-30"></div>
    <div class="container mx-auto px-6 relative z-10">
        <div class="text-center text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">Hubungi Kami</h1>
            <p class="text-xl max-w-3xl mx-auto">Kami siap membantu Anda dengan layanan terbaik</p>
        </div>
    </div>
</section>

<!-- Kontak Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-6">
        <!-- Skeleton Loader -->
        <div id="skeleton-loader" class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Info Kontak Skeleton -->
            <div class="lg:col-span-1">
                <div class="h-8 w-48 bg-gray-200 rounded-md mb-8 skeleton-shimmer"></div>
                
                <div class="space-y-8">
                    <!-- Alamat Skeleton -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-full bg-gray-200 mr-4 skeleton-shimmer"></div>
                        <div>
                            <div class="h-6 w-32 bg-gray-200 rounded-md mb-2 skeleton-shimmer"></div>
                            <div class="h-4 w-48 bg-gray-200 rounded-md mb-1 skeleton-shimmer"></div>
                            <div class="h-4 w-40 bg-gray-200 rounded-md skeleton-shimmer"></div>
                        </div>
                    </div>
                    
                    <!-- Telepon Skeleton -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-full bg-gray-200 mr-4 skeleton-shimmer"></div>
                        <div>
                            <div class="h-6 w-32 bg-gray-200 rounded-md mb-2 skeleton-shimmer"></div>
                            <div class="h-4 w-36 bg-gray-200 rounded-md mb-1 skeleton-shimmer"></div>
                            <div class="h-4 w-40 bg-gray-200 rounded-md skeleton-shimmer"></div>
                        </div>
                    </div>
                    
                    <!-- Email Skeleton -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-full bg-gray-200 mr-4 skeleton-shimmer"></div>
                        <div>
                            <div class="h-6 w-32 bg-gray-200 rounded-md mb-2 skeleton-shimmer"></div>
                            <div class="h-4 w-44 bg-gray-200 rounded-md mb-1 skeleton-shimmer"></div>
                            <div class="h-4 w-40 bg-gray-200 rounded-md skeleton-shimmer"></div>
                        </div>
                    </div>
                    
                    <!-- Jam Operasional Skeleton -->
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-full bg-gray-200 mr-4 skeleton-shimmer"></div>
                        <div>
                            <div class="h-6 w-32 bg-gray-200 rounded-md mb-2 skeleton-shimmer"></div>
                            <div class="h-4 w-52 bg-gray-200 rounded-md mb-1 skeleton-shimmer"></div>
                            <div class="h-4 w-44 bg-gray-200 rounded-md mb-1 skeleton-shimmer"></div>
                            <div class="h-4 w-28 bg-gray-200 rounded-md skeleton-shimmer"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8">
                    <div class="h-6 w-28 bg-gray-200 rounded-md mb-3 skeleton-shimmer"></div>
                    <div class="flex space-x-4">
                        <div class="w-10 h-10 rounded-full bg-gray-200 skeleton-shimmer"></div>
                        <div class="w-10 h-10 rounded-full bg-gray-200 skeleton-shimmer"></div>
                        <div class="w-10 h-10 rounded-full bg-gray-200 skeleton-shimmer"></div>
                        <div class="w-10 h-10 rounded-full bg-gray-200 skeleton-shimmer"></div>
                    </div>
                </div>
            </div>
            
            <!-- Form Kontak Skeleton -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-100">
                    <div class="h-8 w-40 bg-gray-200 rounded-md mb-8 skeleton-shimmer"></div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <div class="h-5 w-32 bg-gray-200 rounded-md mb-2 skeleton-shimmer"></div>
                            <div class="h-10 bg-gray-200 rounded-lg w-full skeleton-shimmer"></div>
                        </div>
                        
                        <div>
                            <div class="h-5 w-24 bg-gray-200 rounded-md mb-2 skeleton-shimmer"></div>
                            <div class="h-10 bg-gray-200 rounded-lg w-full skeleton-shimmer"></div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <div class="h-5 w-24 bg-gray-200 rounded-md mb-2 skeleton-shimmer"></div>
                        <div class="h-10 bg-gray-200 rounded-lg w-full skeleton-shimmer"></div>
                    </div>
                    
                    <div class="mb-6">
                        <div class="h-5 w-24 bg-gray-200 rounded-md mb-2 skeleton-shimmer"></div>
                        <div class="h-32 bg-gray-200 rounded-lg w-full skeleton-shimmer"></div>
                    </div>
                    
                    <div class="flex justify-end">
                        <div class="h-12 w-36 bg-gray-200 rounded-lg skeleton-shimmer"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Konten asli (tersembunyi saat loading) -->
        <div id="kontak-content" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Info Kontak -->
            <div class="lg:col-span-1">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Informasi Kontak</h2>
                
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">Alamat Kantor</h3>
                            <p class="text-gray-600">Jl. Letjend Pol. Soemarto No.127, Watumas, Purwanegara,<br>Kec. Purwokerto Utara, Kabupaten Banyumas, Jawa Tengah 53127</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <i class="fas fa-phone-alt text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">Telepon</h3>
                            <p class="text-gray-600">+62 341 123456</p>
                            <p class="text-gray-600">+62 812 3456 7890</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <i class="fas fa-envelope text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">Email</h3>
                            <p class="text-gray-600">rentalmobil@gmail.com</p>
                            <p class="text-gray-600">cs@rentalmobil.com</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <i class="fas fa-clock text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">Jam Operasional</h3>
                            <p class="text-gray-600">Senin - Jumat: 08.00 - 17.00</p>
                            <p class="text-gray-600">Sabtu: 09.00 - 15.00</p>
                            <p class="text-gray-600">Minggu: Tutup</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Ikuti Kami</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white p-3 rounded-full transition-all">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white p-3 rounded-full transition-all">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white p-3 rounded-full transition-all">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white p-3 rounded-full transition-all">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Form Kontak -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Kirim Pesan</h2>
                    
                    <?php if ($pesan_sukses): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-3"></i>
                                <div>
                                    <p class="font-medium">Pesan Terkirim!</p>
                                    <p class="text-sm">Terima kasih telah menghubungi kami. Tim kami akan segera menghubungi Anda kembali.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                            <p class="font-medium">Mohon perbaiki error berikut:</p>
                            <ul class="list-disc list-inside text-sm">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="kontak.php" method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="nama" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($nama ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="subjek" class="block text-gray-700 text-sm font-medium mb-2">Subjek <span class="text-red-500">*</span></label>
                            <input type="text" id="subjek" name="subjek" value="<?= htmlspecialchars($subjek ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        
                        <div class="mb-6">
                            <label for="pesan" class="block text-gray-700 text-sm font-medium mb-2">Pesan <span class="text-red-500">*</span></label>
                            <textarea id="pesan" name="pesan" rows="5" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"><?= htmlspecialchars($pesan ?? '') ?></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all font-medium">
                                <i class="fas fa-paper-plane mr-2"></i> Kirim Pesan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-6">
        <!-- Map loading skeleton -->
        <div id="map-skeleton" class="mb-8">
            <div class="h-8 w-48 bg-gray-200 rounded-md mb-4 mx-auto skeleton-shimmer"></div>
            <div class="h-1 w-20 bg-gray-200 rounded-full mb-6 mx-auto skeleton-shimmer"></div>
            <div class="h-4 w-96 bg-gray-200 rounded-md mb-6 mx-auto skeleton-shimmer"></div>
            <div class="h-96 bg-gray-200 rounded-xl skeleton-shimmer"></div>
        </div>
        
        <!-- Map content -->
        <div id="map-content" class="hidden">
            <div class="mb-8 text-center">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Lokasi Kami</h2>
                <div class="w-20 h-1 bg-blue-600 mx-auto mb-6 rounded-full"></div>
                <p class="text-gray-600 max-w-2xl mx-auto">Kunjungi kantor kami untuk konsultasi langsung atau informasi lebih lanjut</p>
            </div>
            <div class="rounded-xl overflow-hidden shadow-lg h-96 border-4 border-white hover:shadow-xl transition-all duration-300">
                <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d998.8176978056359!2d109.23165246817884!3d-7.401096124732302!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e655ef25207e1e1%3A0xcedb82ef04ed7e7c!2sUniversitas%20Amikom%20Purwokerto!5e0!3m2!1sid!2sid!4v1747828208267!5m2!1sid!2sid" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="w-full h-full"></iframe>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan konten asli setelah simulasi loading
    setTimeout(function() {
        document.getElementById('skeleton-loader').classList.add('hidden');
        document.getElementById('kontak-content').classList.remove('hidden');
        document.getElementById('map-skeleton').classList.add('hidden');
        document.getElementById('map-content').classList.remove('hidden');
    }, 1500); // 1.5 detik simulasi loading
});
</script>

<?php require_once 'includes/footer.php'; ?> 