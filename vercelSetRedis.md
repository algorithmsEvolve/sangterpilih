# Panduan Konfigurasi Upstash Redis untuk Laravel di Vercel

Upstash Redis menggunakan koneksi yang dienkripsi (TLS/SSL), sehingga ada sedikit perbedaan konfigurasi dibandingkan Redis lokal yang berjalan tanpa TLS. Berikut adalah panduan langkah demi langkah untuk menghubungkan aplikasi Laravel Anda di Vercel dengan Upstash Redis.

## 1. Buat Database Upstash Redis
Anda bisa membuat database Redis melalui dua cara utama:

- **Via Vercel Integration (Paling Mudah):** 
  Di dashboard Vercel Anda, masuk ke **Project** > pilih tab **Storage** > klik **Create Database** > pilih **Upstash Redis** (atau **Vercel KV** yang juga berbasis Upstash).
- **Via Upstash Console:** 
  Daftar dan login di [console.upstash.com](https://console.upstash.com/), lalu buat database Redis baru. Pastikan memilih *region* (lokasi server) yang sama atau berdekatan dengan *region* aplikasi Vercel Anda agar latensinya serendah mungkin.

## 2. Dapatkan Kredensial Koneksi
Setelah database berhasil dibuat, buka detail database tersebut. Anda akan menemukan kredensial atau *connection string*. Anda bisa menggunakan format **Redis URL** (umumnya berawalan `rediss://...`) atau menggunakan kombinasi **Host**, **Port**, dan **Password** secara terpisah.

## 3. Konfigurasi Environment Variables di Vercel
Buka dashboard Vercel project Anda, navigasi ke tab **Settings** > **Environment Variables**. Tambahkan variabel-variabel berikut berdasarkan kredensial dari Upstash:

### Opsi A: Menggunakan REDIS_URL (Paling Direkomendasikan)
Laravel bisa secara otomatis mengekstrak informasi host, port, dan password dari `REDIS_URL`.
*   `REDIS_URL` = `rediss://default:YOUR_PASSWORD@YOUR_UPSTASH_ENDPOINT:PORT` 
    *(Pastikan menggunakan `rediss://` dengan dua huruf 's' karena ini menandakan koneksi TLS/SSL)*
*   `REDIS_CLIENT` = `predis`

### Opsi B: Menggunakan Host, Port, dan Password Secara Terpisah
Jika Anda tidak ingin menggunakan URL tunggal, Anda wajib menambahkan prefix `tls://` pada host.
*   `REDIS_HOST` = `tls://YOUR_UPSTASH_ENDPOINT` *(Sangat penting menambahkan prefix `tls://`)*
*   `REDIS_PASSWORD` = `YOUR_PASSWORD`
*   `REDIS_PORT` = `PORT_DARI_UPSTASH` *(Upstash menggunakan port acak, bukan 6379)*
*   `REDIS_CLIENT` = `predis`

> **Catatan Penting: Predis vs Phpredis**
> Sangat disarankan menggunakan `predis` (seperti yang tertulis pada `.env` Anda: `REDIS_CLIENT=predis`) karena lebih kompatibel dijalankan pada environment *serverless* seperti Vercel tanpa perlu mengatur ekstensi PHP tambahan. Pastikan paket predis sudah terinstall via composer (`composer require predis/predis`).

## 4. Konfigurasi Penggunaan Redis pada Laravel
Tambahkan atau sesuaikan juga variabel berikut pada dashboard Vercel Anda agar Laravel menggunakan Redis untuk fungsi *Cache* dan *Session*:

*   `CACHE_STORE` = `redis`
*   `SESSION_DRIVER` = `redis`

## 5. Redeploy Aplikasi Anda
Setelah Anda menyimpan semua perubahan konfigurasi *Environment Variables* di Vercel, Anda wajib melakukan proses **Redeploy** agar aplikasi dirender ulang dengan variabel yang baru.

1. Pergi ke tab **Deployments** di project Vercel Anda.
2. Klik tombol titik tiga (menu) pada deployment terbaru Anda dan pilih **Redeploy**.

Setelah redeploy selesai, *Session* dan *Cache* aplikasi Laravel Anda kini sepenuhnya terhubung ke Upstash Redis!
