1. **Instal Ngrok**
   - Unduh Ngrok dari [ngrok.com](https://ngrok.com/download)
   - Ekstrak file yang diunduh
   - Pada Windows, buka command prompt/PowerShell

2. **Buat akun Ngrok**
   - Daftar akun gratis di [ngrok.com](https://ngrok.com/signup)
   - Dapatkan authtoken dari dashboard Ngrok

3. **Konfigurasi Ngrok**
   - Jalankan perintah berikut untuk menambahkan authtoken Anda:
     ```
     ngrok config add-authtoken YOUR_AUTH_TOKEN
     ```

4. **Buat tunnel untuk server lokal Anda**
   - Pastikan aplikasi Rental Mobil Anda sudah berjalan di server lokal (misalnya di port 80 atau 8080)
   - Jalankan perintah berikut untuk membuat tunnel (sesuaikan dengan port server Anda):
     ```
     ngrok http 80
     ```
   - Atau jika menggunakan Laragon dengan port 80:
     ```
     ngrok http --domain=YOUR_RESERVED_DOMAIN.ngrok.io 80
     ```

5. **Akses URL dari handphone**
   - Ngrok akan memberikan URL publik (misalnya https://b2d6-158-140-167-45.ngrok-free.app/Rental%20Mobil/user/index.php)
   - Salin URL tersebut dan buka di browser handphone Anda

## Ringkasan Perubahan

1. **File yang dimodifikasi:**
   
   - **user/index.php**
     - Menghapus filter `WHERE m.status = 'tersedia'` untuk menampilkan semua mobil
     - Menambahkan kode untuk mendapatkan informasi tanggal pengembalian mobil
     - Menambahkan tampilan status "Sedang Disewa" pada mobil yang sedang disewa
     - Menambahkan tampilan tanggal kapan mobil akan tersedia kembali
     - Mengubah tampilan tombol detail untuk mobil yang sedang disewa

   - **user/ajax/related_cars.php**
     - Menghapus filter `WHERE m.status = 'tersedia'` agar menampilkan semua mobil
     - Menambahkan kode untuk mendapatkan informasi tanggal pengembalian
     - Menambahkan tampilan status dan tanggal pengembalian
     - Mengubah tampilan tombol detail untuk mobil yang sedang disewa

2. **Perubahan pada UI:**
   
   - Mobil yang sedang disewa ditampilkan dengan badge "Sedang Disewa"
   - Overlay menampilkan tanggal kapan mobil akan tersedia kembali
   - Tombol detail untuk mobil yang disewa diubah menjadi warna kuning
   - Gambar mobil yang sedang disewa diberi efek opacity

Dengan perubahan ini, pengguna sekarang dapat melihat semua mobil termasuk yang sedang disewa di halaman utama dan halaman mobil. Informasi status dan tanggal pengembalian membantu pengguna mengetahui kapan mobil akan tersedia kembali.