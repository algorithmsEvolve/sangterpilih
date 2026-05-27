# Codebase

## Ringkasan

Sang Terpilih adalah game multiplayer turn-based berbasis Laravel 13. State game aktif disimpan di Redis sebagai JSON per room, sedangkan sinkronisasi UI antar pemain dikirim lewat Laravel broadcasting ke channel room.

Brand publik aplikasi adalah **Sang Terpilih**. Nama repository/folder masih `number-battle`, sehingga nama internal lama bisa tetap muncul sebagai konteks teknis non-UI.

Stack utama:

- Backend: Laravel 13, PHP 8.3+
- State runtime: Redis melalui `predis/predis`
- Realtime: Laravel Reverb/Pusher-compatible broadcasting, Laravel Echo, Pusher JS
- Frontend: Blade, Alpine.js CDN, Tailwind CDN pada view
- Build asset: Vite, Tailwind CSS dependency, Laravel Vite plugin

## Entry Point

File rute utama adalah `routes/web.php`.

Rute aktif:

- `GET /`: halaman landing untuk buat room, tombol gabung room, dan katalog kartu.
- `GET /rooms`: halaman daftar room waiting yang bisa langsung di-join.
- `GET /rooms/list`: endpoint JSON daftar room waiting untuk refresh realtime.
- `POST /room/create`: membuat room Redis.
- `POST /room/join`: join ke room Redis.
- `GET /room/{code}`: halaman game.
- `POST /room/{code}/start`: host memulai game.
- `POST /room/{code}/roll`: pemain aktif melempar dadu.
- `POST /room/{code}/end-turn`: pemain aktif mengakhiri giliran.
- `POST /room/{code}/shop/buy`: membeli kartu legacy/shop.
- `POST /room/{code}/cards/use`: memakai kartu.
- `POST /room/{code}/cards/skip-trap`: route masih terdaftar tetapi method terkait tidak ditemukan di controller saat ini.
- `POST /room/{code}/submit-loadout`: memilih loadout survival.
- `POST /room/{code}/leave`: keluar room atau menutup room jika host.

Rute Eloquent/database lama dikomentari di file yang sama. Implementasi lama berada di `app/Http/Controllers/GameController.php`.

## Struktur Folder Penting

`app/Http/Controllers`

- `GameRedisController.php`: controller utama yang aktif. Berisi validasi request, transisi state game, pemilihan mode game, pemakaian kartu, dan broadcast state.
- `GameController.php`: controller lama berbasis Eloquent/database. Tidak aktif di route saat ini.

`app/Repositories`

- `RoomRedisRepository.php`: abstraction tipis untuk menyimpan, mengambil, menghapus, dan membuat state awal room di Redis.

`app/Services/GameModes`

- `GameModeInterface.php`: kontrak mode game.
- `ClassicMode.php`: skor mulai dari 0, roll menambah score, game selesai saat ronde melewati total ronde.
- `SurvivalMode.php`: LP mulai 2000 atau 3000, roll menjadi damage, banyak buff/trap diproses di sini, game selesai saat ada pemain LP <= 0.

`app/Services/Cards`

- `CardEffectInterface.php`: kontrak efek kartu.
- `Spells/*.php`: implementasi efek spell.
- `Traps/*.php`: implementasi efek trap.

`app/Events`

Event broadcast realtime. Semua memakai `ShouldBroadcastNow` dan channel publik `room.{roomCode}`.

- `RoomStateUpdated`
- `DiceRolled`
- `CardEffectUsed`
- `GameStarted`
- `GameOver`
- `PlayerJoined`
- `PlayerLeft`
- `RoomClosed`
- `RoomsUpdated`
- `TurnChanged` tersedia, tetapi tidak tampak dipakai oleh controller aktif.

`resources/views`

- `welcome.blade.php`: halaman awal berisi brand Sang Terpilih, gambar imam dari URL raw GitHub, form `Buat Room`, tombol `Gabung Room`, dan katalog kartu.
- `rooms.blade.php`: halaman daftar room waiting, subscribe channel `rooms`, refresh daftar room via `/rooms/list`, dan modal input nama sebelum join.
- `room.blade.php`: layout utama game, Alpine component, Echo listener, fetch action, inventory, shop, loadout, toast, modal efek kartu, animasi dadu, animasi LP/score, dan sequence game over.
- `classicRoom.blade.php`: wrapper mode classic, extend `room`.
- `survivalRoom.blade.php`: wrapper mode survival, extend `room`.

`resources/js`

- `echo.js`: setup Echo via Vite untuk Reverb. View `room.blade.php` juga membuat instance Echo inline.
- `bootstrap.js`: setup Axios default.
- `app.js`: entry asset standar Laravel.

`config`

- `cards.php`: katalog spell/trap survival dan mapping `effect_class`. Kartu dynamic memiliki `icon`; sebagian kartu legacy memiliki `image_url`.
- `broadcasting.php`: koneksi `reverb`, `pusher`, `ably`, `log`, `null`.
- `database.php`: konfigurasi Redis client dari `REDIS_CLIENT`.
- `session.php`, `cache.php`, `queue.php`: driver runtime Laravel.

`database`

Migration, model `Room`, dan model `Player` masih ada untuk implementasi database lama. Jalur Redis aktif tidak memakai tabel `rooms`/`players` untuk game state.

## Dependency Penting

Composer:

- `laravel/framework`
- `laravel/reverb`
- `predis/predis`
- `pusher/pusher-php-server`
- `laravel/tinker`

NPM:

- `laravel-echo`
- `pusher-js`
- `vite`
- `laravel-vite-plugin`
- `tailwindcss`
- `concurrently`

## Runtime State

State room aktif tersimpan di Redis key:

```text
room:{code}
```

TTL key adalah 7200 detik atau 2 jam. Setiap `saveRoom()` memperbarui TTL.

Session menyimpan:

```text
player_id
```

Session ini menjadi identitas pemain di room. Tidak ada login permanen untuk pemain game.

## Frontend UI Aktif

Halaman awal:

- Judul brand: `Sang Terpilih`.
- Asset brand: `https://raw.githubusercontent.com/algorithmsEvolve/sangterpilih/refs/heads/main/public/images/sang-terpilih-imam.png`.
- Tagline: `Lempar dadumu, pasrahkan nasibmu, hindarilah tanggung jawab berat menjadi Imam!`
- Form utama: `Buat Room`.
- Mode default: `survival`.
- Join dilakukan lewat tombol `Gabung Room` menuju `/rooms`.

Halaman daftar room:

- Menampilkan hanya room status `waiting`.
- Tombol `Gabung Room` membuka modal nama pemain.
- Refresh manual tersedia dan refresh realtime dipicu event `RoomsUpdated`.

Halaman game:

- Dadu tetap tampil sebelum pemain roll; teks `just rolled ...` hanya tampil setelah roll valid selesai.
- Survival menyembunyikan tombol `Shop`.
- Inventory menampilkan kartu milik pemain aktif.
- Kartu memakai helper frontend `cardArtHtml()` untuk menampilkan `image_url` jika ada atau fallback `icon` bila tidak ada.
- Perubahan LP/score dianimasikan secara bertahap melalui `displayScore`, `scoreDelta`, dan `animatePlayerScore()`.
- Perubahan LP dari roll ditunda sampai animasi dadu dan burst angka roll selesai.
- Perubahan LP dari efek kartu ditunda sampai modal efek kartu tertutup.
- Game over survival menampilkan spotlight winner/loser sebelum leaderboard akhir.

## Status Implementasi

Bagian yang aktif dan perlu dijadikan sumber kebenaran:

- `routes/web.php`
- `GameRedisController`
- `RoomRedisRepository`
- `config/cards.php`
- `ClassicMode`, `SurvivalMode`
- `resources/views/room.blade.php`

Bagian historis/legacy:

- `GameController`
- `Room` dan `Player` Eloquent untuk gameplay
- migration custom room/player
