# Sistem Rental Mobil Terdistribusi

Sistem Rental Mobil Terdistribusi adalah aplikasi berbasis microservices untuk manajemen dan pemesanan rental mobil yang diimplementasikan dengan menggunakan arsitektur sistem terdistribusi.

## Arsitektur Sistem

Sistem ini diimplementasikan menggunakan arsitektur microservices dengan beberapa komponen utama:

### API Gateway
- Berperan sebagai titik masuk tunggal untuk semua permintaan API
- Menangani routing ke microservices yang sesuai
- Mengelola otentikasi, autorisasi, dan CORS

### Microservices
1. **User Service**
   - Mengelola data pengguna (registrasi, login, profil)
   - Menangani otentikasi dengan JWT

2. **Vehicle Service**
   - Mengelola data kendaraan
   - Menyediakan operasi CRUD untuk kendaraan
   - Fitur pencarian dan filtering kendaraan

3. **Booking Service**
   - Mengelola proses pemesanan kendaraan
   - Mengecek ketersediaan kendaraan
   - Menangani status pemesanan

4. **Payment Service**
   - Memproses pembayaran
   - Verifikasi pembayaran
   - Riwayat pembayaran

5. **Review Service**
   - Mengelola ulasan kendaraan
   - Rating kendaraan dari pengguna

6. **Notification Service**
   - Mengirim notifikasi ke pengguna
   - Menggunakan RabbitMQ sebagai message broker

### Infrastruktur Pendukung
- **Database Terpisah** - Setiap service memiliki database sendiri
- **Redis** - Untuk caching
- **RabbitMQ** - Sebagai message broker
- **Consul** - Untuk service discovery
- **Prometheus & Grafana** - Untuk monitoring
- **Docker** - Untuk kontainerisasi

## Teknologi yang Digunakan

- **Backend**
  - PHP dengan Slim Framework
  - JWT untuk otentikasi
  - PDO untuk akses database
  - Redis untuk caching

- **Database**
  - MySQL

- **Infrastruktur**
  - Docker & Docker Compose
  - Apache sebagai web server
  - Redis untuk caching
  - RabbitMQ untuk message broker
  - Prometheus & Grafana untuk monitoring
  - Consul untuk service discovery

## Cara Menjalankan

### Prasyarat
- Docker dan Docker Compose
- Git

### Langkah-langkah
1. Clone repositori ini
   ```
   git clone https://github.com/username/rental-mobil.git
   cd rental-mobil
   ```

2. Buat file `.env` pada setiap service berdasarkan file `.env.example`
   ```
   cp api-gateway/env.example api-gateway/.env
   cp services/user-service/env.example services/user-service/.env
   cp services/vehicle-service/env.example services/vehicle-service/.env
   # Dan seterusnya untuk service lainnya
   ```

3. Jalankan dengan Docker Compose
   ```
   docker-compose up -d
   ```

4. Akses aplikasi melalui API Gateway
   - API Gateway: http://localhost:8000
   - Prometheus: http://localhost:9090
   - Grafana: http://localhost:3000
   - RabbitMQ Management: http://localhost:15672
   - Consul UI: http://localhost:8500

## Dokumentasi API

### API Gateway Endpoints

#### User Service
- `POST /api/users/register` - Registrasi user baru
- `POST /api/users/login` - Login user
- `GET /api/users` - Mendapatkan semua users (admin only)
- `GET /api/users/{id}` - Mendapatkan detail user
- `PUT /api/users/{id}` - Mengupdate user
- `DELETE /api/users/{id}` - Menghapus user (admin only)

#### Vehicle Service
- `GET /api/vehicles` - Mendapatkan daftar kendaraan
- `GET /api/vehicles/{id}` - Mendapatkan detail kendaraan
- `POST /api/vehicles` - Menambahkan kendaraan baru (admin only)
- `PUT /api/vehicles/{id}` - Mengupdate kendaraan (admin only)
- `DELETE /api/vehicles/{id}` - Menghapus kendaraan (admin only)

#### Booking Service
- `GET /api/bookings` - Mendapatkan semua bookings (admin only)
- `GET /api/bookings/user/{userId}` - Mendapatkan bookings user
- `GET /api/bookings/{id}` - Mendapatkan detail booking
- `POST /api/bookings` - Membuat booking baru
- `PUT /api/bookings/{id}/status` - Mengupdate status booking (admin only)
- `DELETE /api/bookings/{id}` - Membatalkan booking

#### Payment Service
- `GET /api/payments` - Mendapatkan semua payment (admin only)
- `GET /api/payments/booking/{bookingId}` - Mendapatkan payment untuk booking
- `POST /api/payments` - Memproses pembayaran
- `GET /api/payments/verify/{paymentId}` - Memverifikasi payment

#### Review Service
- `GET /api/reviews/vehicle/{vehicleId}` - Mendapatkan reviews kendaraan
- `POST /api/reviews` - Menambahkan review
- `PUT /api/reviews/{id}` - Mengupdate review
- `DELETE /api/reviews/{id}` - Menghapus review

## Login Default

### Admin
- Username: admin
- Password: admin123

### User
- Username: budi
- Password: password123

## Kelebihan Sistem Terdistribusi

1. **Skalabilitas** - Setiap microservice dapat di-scale secara independen
2. **Ketahanan** - Kegagalan pada satu service tidak mempengaruhi keseluruhan sistem
3. **Pengembangan Independen** - Tim dapat bekerja pada service yang berbeda secara independen
4. **Teknologi Heterogen** - Setiap service dapat menggunakan teknologi yang paling sesuai
5. **Deployment Independen** - Perubahan dapat di-deploy tanpa mempengaruhi seluruh sistem

## Pengaturan Cron Job untuk Notifikasi

Untuk mengirim notifikasi pengingat pengembalian mobil secara otomatis, tambahkan cron job berikut:

```bash
# Menjalankan setiap hari pada jam 8 pagi
0 8 * * * php /path/to/admin/send_return_reminder.php
```

Pastikan untuk mengganti `/path/to/` dengan path absolut ke direktori aplikasi.

## Login Admin Default

- Username: admin
- Password: admin123

## Kontribusi

Kontribusi, masukan, dan saran sangat diterima. Silakan buat issue atau pull request. 