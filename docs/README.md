# Sang Terpilih Docs

Dokumentasi ini memetakan struktur kode, pengetahuan domain, dan alur eksekusi aplikasi berdasarkan codebase saat ini.

## Isi

- [Codebase](./codebase.md): peta folder, komponen utama, dependency, entry point, view aktif, dan asset branding.
- [Code Knowledge](./code-knowledge.md): model state room, mode game, sistem kartu, broadcast event, state frontend, dan aturan penting.
- [Code Flow](./code-flow.md): alur buat/gabung room, daftar room realtime, start game, loadout, roll dice, use card, end turn, leave room, dan sinkronisasi UI.

## Catatan Konteks

Rute aktif saat ini memakai implementasi Redis melalui `GameRedisController` dan `RoomRedisRepository`. Implementasi Eloquent/database lama masih ada di `GameController`, `Room`, `Player`, dan migration sebagai jalur historis/backup, tetapi route di `routes/web.php` sedang diarahkan ke Redis.

Brand aplikasi di UI adalah **Sang Terpilih**. Nama folder repository masih `number-battle`, jadi beberapa nama internal, package, atau context lama bisa tetap memakai istilah historis selama tidak tampil sebagai teks branding di UI.

## Update Terbaru Yang Perlu Diingat

- Halaman awal hanya menyediakan form `Buat Room`, katalog kartu, dan tombol `Gabung Room` menuju daftar room.
- Halaman `/rooms` menampilkan room waiting, refresh via event `RoomsUpdated`, dan membuka modal nama sebelum join.
- Mode default saat membuat room adalah `survival`.
- Fase survival loadout berlangsung 2 menit, memakai `selectionEndTime` dan `serverTime`, dan game otomatis mulai saat semua pemain ready atau timer habis.
- UI kartu memakai `image_url` jika ada, lalu fallback ke `icon` dari katalog agar area gambar tidak blank.
- Animasi perubahan LP/score ditahan saat modal efek kartu tampil, lalu dijalankan setelah modal tertutup.
