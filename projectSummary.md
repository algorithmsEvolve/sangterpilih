# Ringkasan Proyek: Sang Terpilih (Number Battle)

## 📌 Tentang Aplikasi Ini

**Sang Terpilih (Number Battle)** adalah sebuah permainan (game) multiplayer berbasis web yang berjalan secara *real-time*. Game ini mengusung genre *turn-based strategy* kasual di mana pemain berlomba mengumpulkan poin dan saling menjegal menggunakan kartu aksi.

Dalam permainan ini, pemain tidak hanya mengandalkan keberuntungan dari lemparan dadu, tetapi juga strategi dalam mengelola sumber daya (poin) untuk membeli item (kartu Spell atau Trap) yang dapat memberikan keuntungan bagi diri sendiri atau memberikan kerugian bagi lawan.

Game ini dirancang untuk dapat dimainkan bersama teman-teman dengan ritme cepat (*drop-in drop-out*), tanpa memerlukan proses registrasi atau login yang rumit. Sistem identitas menggunakan *session* sementara yang terikat saat pemain masuk ke dalam arena (Room).

---

## ⚙️ Bagaimana Aplikasi Ini Bekerja

Sistem dan alur kerja aplikasi dibagi menjadi beberapa fase permainan:

### 1. Sistem Lobby (Pembuatan dan Bergabung ke Room)
*   **Create Room:** Seorang pemain dapat membuat sebuah ruangan baru (Room). Pemain yang membuat room ini akan bertindak sebagai "Host" dan mendapatkan sebuah Kode Room unik.
*   **Join Room:** Pemain lain dapat bergabung ke dalam permainan menggunakan Kode Room yang diberikan oleh Host.
*   **Session Binding:** Begitu pemain masuk atau membuat room, sistem backend akan menyimpan identitas mereka di *Session* (berbasis Redis). Tidak ada tabel `users` di database yang mengikat data pribadi mereka secara permanen.

### 2. Alur Permainan (Gameplay)
*   **Turn-Based (Bergiliran):** Permainan berjalan secara bergiliran. Server akan menentukan siapa yang memiliki giliran saat ini. Pemain yang belum mendapat giliran tidak bisa melempar dadu atau melakukan aksi tertentu.
*   **Roll Dice (Lempar Dadu):** Pada gilirannya, pemain melempar dadu virtual. Hasil lemparan dadu (angka 1-6) akan dikonversi menjadi poin yang ditambahkan ke total skor pemain tersebut.
*   **Shop & Inventory:** Poin yang terkumpul berfungsi sebagai 'mata uang'. Pemain dapat menggunakan poin ini untuk membeli kartu di dalam *Shop*. Kartu yang dibeli akan masuk ke *Inventory* pemain.

### 3. Sistem Kartu (Spell & Trap)
Pemain dapat menggunakan kartu dari *Inventory* mereka untuk memanipulasi jalannya permainan:
*   **Spell Cards (Efek Instan/Positif):** Memberikan keuntungan langsung kepada pengguna. Contoh: *Multiplier Spell* yang menggandakan hasil poin dari lemparan dadu pemain pada giliran tersebut.
*   **Trap Cards (Efek Serangan/Negatif):** Digunakan untuk menyerang atau menghalangi lawan. Contoh: *Skip Trap* yang membuat lawan kehilangan gilirannya.

### 4. Sinkronisasi Real-Time (Teknologi di Balik Layar)
Kunci dari pengalaman multiplayer game ini adalah sinkronisasi *real-time*:
*   **Laravel Reverb (WebSocket):** Setiap aksi yang dilakukan pemain (seperti melempar dadu, membeli kartu, menggunakan kartu) akan dikirim ke server. Server kemudian memvalidasi aksi tersebut dan mem-*broadcast* (menyiarkan) state terbaru ke semua pemain di room yang sama melalui koneksi WebSocket.
*   **Reaktivitas Frontend:** Frontend (dibangun dengan Blade, Tailwind, dan Alpine.js) mendengarkan siaran (*broadcast*) ini. Ketika ada pembaruan data (misalnya giliran berpindah), antarmuka pengguna (UI) akan langsung berubah dan memicu animasi (seperti dadu berputar) secara instan tanpa perlu memuat ulang (*refresh*) halaman web.

### 5. Penyelesaian dan Keamanan
*   **Anti-Cheat Ringan:** Semua logika dan perhitungan poin, serta giliran, divalidasi secara ketat oleh Server (Backend). Pemain tidak bisa memanipulasi poin mereka sendiri menggunakan inspeksi elemen atau API *tools*.
*   **Pembersihan Otomatis:** Jika semua pemain meninggalkan ruangan atau menutup *browser*, sistem dapat membersihkan data *Room* dan *State* yang menggantung agar tidak membebani server dan database.

---

## 🛠️ Stack Teknologi Utama
*   **Backend:** Laravel 13 (PHP 8.3)
*   **Frontend:** Blade Templates, Tailwind CSS v4, Alpine.js
*   **Real-time Engine:** Laravel Reverb (Broadcasting) & Echo
*   **State & Session:** Upstash Redis / Redis
*   **Database:** SQLite / MySQL (dengan implementasi kolom JSON untuk menyimpan inventaris kartu dan riwayat)
