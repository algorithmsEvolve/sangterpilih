# Panduan Optimasi Supabase & Vercel untuk Performa Maksimal

Dokumen ini menjelaskan langkah-langkah spesifik untuk mengatur arsitektur serverless **Vercel** dan **Supabase** agar aplikasi (terutama yang bersifat *real-time* seperti game) tetap **100% Gratis** namun memiliki kecepatan respons yang sangat tinggi (*low latency*).

---

## 1. Menyamakan Region (Lokasi Server) Vercel & Supabase
Karena arsitektur Anda terpisah di dua penyedia cloud yang berbeda, jarak geografis adalah musuh utama latensi (keterlambatan pengiriman data).

**Langkah:**
1. **Cek Region Supabase:** 
   - Buka laman Dashboard Supabase -> Project Anda -> **Project Settings** (ikon Roda Gigi di kiri bawah) -> tab **General** -> scroll ke **Project info / Infrastructure**.
   - Catat "Region" Anda (misal: *Singapore (ap-southeast-1)*).
2. **Cek Region Vercel:**
   - Buka Dashboard Vercel -> Project Anda -> pilar tab **Settings** -> **Functions** -> lihat **Function Region**.
3. **Samakan Keduanya:**
   - Ubah wilayah pengaturan **Function Region** di Vercel agar cocok atau letaknya secara geografis sedekat mungkin (misalnya pilih **`sin1 - Singapore`** jika Supabase diletakkan di Asia Tenggara).
4. **Deploy Ulang Vercel:** Supaya Vercel sadar bila lokasi baru telah diaplikasikan.

*(Khusus Advanced)*: Menekan region di kode via `vercel.json`:
```json
{
    "version": 2,
    "regions": ["sin1"]
}
```

---

## 2. Wajib Menggunakan Supavisor (Connection Pooler)
Dalam *Serverless environment* seperti Vercel, framework seperti Laravel akan mencoba membuat koneksi *database* "segar" dari awal di SETIAP kali aksi HTTP terjadi akibat penutupan memori kilat (bersifat sesaat). Supabase (PostgreSQL pada dasarnya) lamban setiap kali menerima permintaan *koneksi baru*. Karena itu, Anda harus menggunakan **Transaction Pooler**.

**Langkah:**
1. Masuk Dashboard Supabase Anda, cari **Database Settings** (Pengaturan Database) -> scroll ke bawah bagian **Connection String**.
2. Centang/Pilih opsi tab bertuliskan **Use connection pooling** (Transaction Pooler).
3. Salin tautan yang diberikan. Ciri khas mutlaknya adalah portnya bukan standar 5432, melainkan port **`6543`**.

Buka file konfigurasi `.env` Anda, pastikan URL Database Anda seperti ini:
```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.xxxxxxx
DB_PASSWORD=password_database_anda
```
> **⚠️ Peringatan:** Jika mencoba mem-migrate DB (`php artisan migrate`), terkadang port 6543 menolak transaksi DDL. Saat menjalankan migrasi secara lokal di komputer (push), tak apa gunakan port 5432 (Session mode). Tapi untuk Vercel (Production), **wajib 6543**.

---

## 3. Pindahkan Beban Session, Cache, & Queue dari Supabase
Jika aplikasi Anda secara otomatis memiliki konfigurasi `SESSION_DRIVER=database`, matikan. Memaksa serverless Vercel terus-terusan bolak-balik memeriksa Session user Anda ke database remote PostgreSQL Supabase itu sangat memboroskan ping/kuota waktu I/O.

**Langkah:**
Ubah *Environment Variables* di tab **Settings -> Environment Variables** Vercel Anda, atau pada file `.env` lokal Anda menjadi:

```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```
*(Untuk platform yang menghargai caching yang konsisten di tengah environment ephemeral pada Vercel, banyak orang menyarankan menggunakan **Upstash Redis - Free Tier**).*

## Ekstra: Kenapa Tidak SQLite?
**SQLite itu gratis & super kencang**. Namun karena Vercel menganut sistem server yang dikunci (*Read-only*) sesaat setelah PHP berjalan, usaha Laravel mengubah tabel/nilai pada file `database.sqlite` akan dihalangi/ditolak. Anda hanya bisa menggunakan SQLite di hosting konvensional/VPS murni. Karena itulah, kombinasi *Vercel + Supabase Pooler* adalah jawaban dari Serverless modern.
