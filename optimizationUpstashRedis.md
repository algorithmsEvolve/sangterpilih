# Panduan Migrasi & Optimasi Serverless dengan Upstash Redis

Dokumen ini menjelaskan mengapa dan bagaimana cara memigrasikan "Number Battle" (Game Real-time) dari penyimpanan tradisional berbasis SQL ke **Upstash Redis** di atas arsitektur *Serverless Vercel*. Metode yang digunakan di sini adalah metode perpindahan paling aman: **Soft Deprecation (A/B Testing)**.

---

## 1. Mengapa Redis Lebih Superior di Vercel?
1. **Tiada Masalah "Cold Start" Koneksi:** Database relasional (Supabase Postgres / MySQL) memaksa fungsi serverless membuka koneksi TCP berat di setiap hitungan detik (*request*). Upstash dibangun dengan REST API terintegrasi yang menelan perintah dan memutusnya dalam hitungan **mikrodetik**, tak akan dihalangi oleh siklus nyala-mati memori Vercel.
2. **Kesesuaian Data (Sifat *Ephemeral*):** Data *room* dan *score* pemain hanyalah data singgah yang tak perlu direkam ke memori fisik SSD untuk selamanya. Sifat Redis yang menaruh file JSON sepenuhnya di RAM membuat eksekusi lempar dadu jadi seperti tanpa kedipan.

---

## 2. Persiapan Infrastruktur Wajib (Langkah Awal)
Berhubung Vercel tidak selalu menjamin dukungan ekstensi sistem bawaan bahasa C untuk PHP (seperti ekstensi server PHP-Redis murni), kita **WAJIB** menggunakan pustaka murni PHP.

**A. Tambahkan Pustaka Predis**
Jalankan perintah ini di terminal proyek Anda untuk merekrut "Kurir":
```bash
composer require predis/predis
```
*(Tindakan ini akan sedikit memodifikasi file `composer.json` dan `composer.lock` Anda).*

**B. Pengaturan file `.env`**
Buat *Database* gratis di **Upstash.com** (atau lewat sub-menu Vercel KV), dan ambil *ConnectionString* rahasianya. Letakkan di `.env`:
```env
REDIS_URL="redis://default:xxxx_rahasia_anda_xxxx@regional-nama.upstash.io:12345"
REDIS_CLIENT=predis

# Paksa Laravel agar otak sistem Session-nya pindah berlindung ke Redis
SESSION_DRIVER=redis
CACHE_STORE=redis
```

---

## 3. Strategi Migrasi Tanpa Resiko (Soft Deprecation)
Konsep A/B Testing memastikan jika kode Redis Anda gagal, Anda hanya butuh 1 detik untuk kembali ke sistem Supabase yang lama.

### Aturan 1: Jangan Sentuh File Lama
Biarkan file `app/Models/Room.php`, `app/Models/Player.php`, dan file `database/migrations/xxxx_create_.....php` **TETAP HIDUP 100%**. Tak perlu dihapus sehuruf pun!

### Aturan 2: Ciptakan Jembatan Data (Repository)
Buat file khusus untuk mengurusi komunikasi dengan peladen Redis di **`app/Repositories/RoomRedisRepository.php`**. Jangan campurkan logika Array kotor di Controller.
```php
<?php
// app/Repositories/RoomRedisRepository.php
namespace App\Repositories;
use Illuminate\Support\Facades\Redis;

class RoomRedisRepository {
    public static function save(string $code, array $state) {
        // Room akan hancur lebur otomasis dalam 2 jam (7200 detik) untuk menghemat RAM!
        Redis::setex("room:{$code}", 7200, json_encode($state)); 
    }
    public static function get(string $code): ?array {
        $json = Redis::get("room:{$code}");
        return $json ? json_decode($json, true) : null;
    }
}
```

### Aturan 3: Bangun Pengendali Kembaran (Controller Baru)
Ubah file Anda yang gemuk `app/Http/Controllers/GameController.php` dengan cara meng*-Cope-Paste* (Salin & Tempel) menjadi file **`GameRedisController.php`**. 
Di dalam *Controller Baru* ini-lah seluruh kode Eloquent (`Room::where()`, `$player->save()`) Anda basmi dan diganti ke metode `$statusArray = RoomRedisRepository::get()`.

### Aturan 4: Sesuaikan Kemudi Rute (Routes)
Pengalihan jalur pamungkas ini dipusatkan HANYA di `routes/web.php`. Anda memberikan "Tanda Komentar" pada rute Supabase yang lama dan menghidupkan jalur menuju Controller Redis rintisan Anda yang baru. 

```php
<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameRedisController;

// 🔴 VERSI SUPABASE/SQL (Ditidurkan sementara oleh 2-Garis Miring)
// Route::post('/room/create', [GameController::class, 'createRoom']);
// Route::post('/room/{code}/roll', [GameController::class, 'rollDice']);

// 🟢 VERSI UPSTASH REDIS (Menyala, ini rute resmi yang baru)
Route::post('/room/create', [GameRedisController::class, 'createRoom']);
Route::post('/room/{code}/roll', [GameRedisController::class, 'rollDice']);
```

---

> **🏆 Kesimpulan:**
> Dengan kerangka *Repository Pattern* dan jalur pengalihan Rute (Routing Splitting) seperti desain di atas, seluruh komponen muka halaman web `room.blade.php` Anda dan *Event WebSockets Reverb* tidak sadar sedikit pun bahwa mesin pembakar energinya tadinya adalah Supabase pelan yang kini diganti penuh menjadi "Kilat Redis". Keamanan terjamin, kecepatan terbukti.
