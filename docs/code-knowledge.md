# Code Knowledge

## Konsep Domain

Sang Terpilih memakai konsep room multiplayer sementara. Pemain membuat atau join room dengan kode, lalu bermain bergiliran. Identitas pemain disimpan dalam session sebagai `player_id`.

Brand UI saat ini adalah `Sang Terpilih`. Nama repository masih `number-battle`, tetapi teks publik di view diarahkan memakai brand baru.

Ada 2 mode:

- `classic`: score race. Roll dadu menambah score. Game selesai setelah jumlah ronde selesai.
- `survival`: LP battle. Roll dadu menjadi damage ke diri sendiri, dan spell/trap memodifikasi damage, roll, atau LP. Game selesai ketika ada pemain LP <= 0.

## State Room Redis

State awal dibuat oleh `RoomRedisRepository::buildInitialRoom()`.

Struktur utama:

```php
[
    'code' => string,
    'mode' => 'classic'|'survival',
    'status' => 'waiting'|'selecting_cards'|'playing'|'finished',
    'current_turn_player_id' => string,
    'current_round' => int,
    'total_rounds' => int,
    'turn_index' => int,
    'turn_has_skip' => bool,
    'turn_multiplier_player_id' => ?string,
    'last_dice_result' => null|int|array,
    'last_roller_name' => ?string,
    'active_turn_snapshot' => ?array,
    'pending_trap_confirmations' => array,
    'trap_target_player_id' => ?string,
    'selection_end_time' => ?int,
    'players' => [
        playerId => [
            'id' => string,
            'name' => string,
            'is_host' => bool,
            'score' => int,
            'has_rolled_this_turn' => bool,
            'inventory' => string[],
            'has_selected_cards' => bool,
            'active_buffs' => string[],
        ],
    ],
]
```

`score` berarti score di classic dan LP di survival. Label UI dibedakan oleh `classicRoom.blade.php` dan `survivalRoom.blade.php`.

## Public State

`GameRedisController::buildRoomState()` membuat state yang dikirim ke frontend lewat JSON response dan broadcast. Inventory disembunyikan secara default supaya inventory pemain lain tidak bocor.

Field public penting:

- `mode`
- `status`
- `currentTurn`
- `currentRound`
- `totalRounds`
- `turnHasSkip`
- `turnMultiplierPlayerId`
- `lastDiceResult`
- `lastRollerName`
- `pendingTrapConfirmations`
- `trapTargetPlayerId`
- `selectionEndTime`
- `serverTime`
- `players`

Inventory pemain aktif dikirim terpisah sebagai `myInventory` dalam response action.

`selectionEndTime` dan `serverTime` dipakai frontend untuk countdown loadout yang sinkron dengan waktu server.

## Daftar Room Realtime

Room waiting bisa dilihat melalui halaman `/rooms`.

Komponen terkait:

- `RoomRedisRepository::listAvailableRooms()`: membaca key `room:*`, memfilter status `waiting`, dan mengembalikan ringkasan room.
- `GameRedisController::roomsView()`: render `resources/views/rooms.blade.php`.
- `GameRedisController::roomsList()`: response JSON `{ rooms: [...] }`.
- `RoomsUpdated`: event broadcast channel `rooms`.

`RoomsUpdated` dipanggil saat perubahan yang memengaruhi daftar room terjadi, misalnya buat room, gabung room, start game, transisi survival dari loadout ke playing, dan leave room.

## Mode Game

### Classic Mode

File: `app/Services/GameModes/ClassicMode.php`

- Initial score: `0`.
- Roll dadu: `score += diceResult`.
- Game over: `current_round > total_rounds`.

Catatan: `processDiceRoll()` mengembalikan array berisi hasil dadu. Controller menyimpan hasil ini sebagai `last_dice_result`.

### Survival Mode

File: `app/Services/GameModes/SurvivalMode.php`

- Initial LP:
  - kurang dari 4 pemain: `2000`
  - 4 pemain atau lebih: `3000`
- Base damage: `sum(rolls) * 100`
- Roll mengurangi LP sendiri, kecuali dimodifikasi buff.
- Game over: ada pemain dengan `score <= 0`.

Survival memproses buff di `active_buffs`. Buff bisa berupa string sederhana seperti `feather_fall`, atau string stateful seperti `karma:{targetId}`, `time_bomb:{turnsLeft}`, `adrenaline_heal:{amount}`.

## Sistem Kartu

Katalog survival berada di `config/cards.php`.

Setiap kartu memiliki:

- `id`
- `name`
- `description`
- `type`: `spell` atau `trap`
- `icon`
- `color`
- `effect_class`

`GameRedisController::cardCatalog()` menggabungkan 2 kartu legacy hardcoded:

- `skip_si`
- `multiplier`

dengan kartu dari `config/cards.php`.

### Interface Efek

Semua efek dinamis mengikuti:

```php
interface CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array;
}
```

Efek memutasi `$room` by reference dan mengembalikan result:

- `success`: efek sukses.
- `error`: pesan error validasi.
- `payload`: data untuk event `CardEffectUsed`.
- `advance_turn`: jika kartu langsung mengakhiri giliran.
- `force_dice_roll_event`: opsional, untuk memaksa broadcast dice event.

Setelah efek sukses, controller menghapus kartu yang dipakai dari inventory pemain.

## Spell

Spell yang tersedia:

- `heal_potion`: heal diri sendiri 500 LP.
- `feather_fall`: damage roll maksimal 100.
- `shield_of_grace`: damage roll dikurangi 50%.
- `loaded_dice_low`: roll pertama dipaksa 1 atau 2.
- `vampiric_aura`: damage menjadi heal.
- `time_skip`: langsung end turn tanpa damage.
- `gamblers_shield`: tebak odd/even. Benar 0 damage, salah damage 2x.
- `rewind`: roll dua kali, ambil hasil terkecil.
- `blood_sacrifice`: bayar persentase LP berdasarkan ronde, curi kartu acak dari lawan.
- `amnesty`: jika LP < 1000, damage menjadi 0.
- `reflect_stance`: jika total dadu >= 5, damage disebar ke lawan dan pemakai 0 damage.
- `equalizer`: samakan LP dengan lawan LP terendah + 300.
- `last_stand`: jika roll membuat mati, LP tersisa 1.
- `adrenaline_rush`: damage normal, giliran berikutnya heal 2x damage.
- `angel_cure`: hapus trap aktif dari pemain.
- `bpjs`: heal diri sendiri 700 dan pemain lain 300.

## Trap

Trap yang tersedia:

- `curse_heavy_bones`: target menerima damage roll 2x.
- `blood_siphon`: curi 300 LP dari target.
- `forced_reroll`: target harus roll 2 dadu.
- `poison_dart`: target langsung menerima 200 damage.
- `karma`: jika pemakai menerima damage > 400, pantulkan ke target.
- `reverse_fortune`: nilai dadu target dibalik, 1 menjadi 6, 2 menjadi 5, dst.
- `sabotage`: target diberi buff `sabotaged`.
- `russian_roulette`: semua pemain menerima 300 damage.
- `time_bomb`: target menerima 800 damage setelah 2 giliran target.

Frontend saat ini memilih target trap secara acak dari pemain lain dan mengirim `is_random: true`.

## Visual Kartu

Ada dua sumber visual kartu:

- `image_url`: dipakai oleh kartu legacy seperti `skip_si` dan `multiplier`, berisi SVG inline.
- `icon`: dipakai oleh kartu dynamic dari `config/cards.php`.

Frontend memakai helper Alpine `cardArtHtml(card, size)` di `room.blade.php`.

Urutan render:

1. Jika `card.image_url` ada, render `image_url`.
2. Jika tidak ada, render fallback icon dengan class `nb-card-icon-art`.

Fallback ini dipakai di:

- Shop card item.
- Inventory card item.
- Survival loadout grid.
- Survival loadout preview.

Tujuannya agar area gambar kartu tidak blank hitam saat kartu hanya punya `icon`.

## Buff dan Multi-roll

`SurvivalMode::processDiceRoll()` menjalankan urutan besar:

1. Dice modifier awal:
   - `loaded_dice_low`
   - `reverse_fortune`
   - `blindfold` disebut di kode, tetapi tidak ditemukan sebagai card aktif di katalog saat ini.
2. Multi-roll:
   - `forced_reroll` memakai `pending_forced_roll` dan `require_extra_roll`.
   - `rewind` memakai `pending_rewind_roll` dan `require_extra_roll`.
3. Damage modifier:
   - `time_skip`
   - `amnesty`
   - `feather_fall`
   - `shield_of_grace`
   - `curse_heavy_bones`
   - `gamblers_shield_odd`
   - `gamblers_shield_even`
4. Special action:
   - `reflect_stance`
   - `karma:{targetId}`
   - `vampiric_aura`
5. Apply damage/heal ke `score`.
6. Post damage:
   - `last_stand`
   - `adrenaline_rush`
   - `adrenaline_heal:{amount}`
   - `time_bomb:{turnsLeft}`
7. Clear one-roll buffs, kecuali state yang eksplisit dipertahankan.

## Realtime Event

Semua event memakai channel publik:

```text
room.{roomCode}
```

Event utama:

- `RoomStateUpdated`: dikirim hampir setiap kali state berubah.
- `DiceRolled`: memicu animasi dadu.
- `CardEffectUsed`: memicu notice dan action history.
- `GameStarted`: penanda game dimulai.
- `GameOver`: mengirim leaderboard dan mengubah status UI.
- `PlayerJoined`: pemain baru join.
- `PlayerLeft`: pemain keluar.
- `RoomClosed`: host keluar, room dihapus.
- `RoomsUpdated`: daftar room waiting berubah dan halaman `/rooms` perlu refresh.

## Frontend State

Frontend utama ada di Alpine component `gameClient()` dalam `resources/views/room.blade.php`.

Tugas utamanya:

- Membuka Echo channel room.
- Mendengar event broadcast.
- Menyimpan local UI state seperti loading, modal, toast, selected loadout.
- Mengirim action dengan `fetch()` JSON ke route Laravel.
- Mengaplikasikan state server lewat `applyState()`.
- Menjaga `myInventory` dari response action.
- Menganimasikan dadu lewat `DiceRolled`.

State UI penting:

- `recentDice`: hasil dadu terakhir dari server.
- `visibleDiceValues()`: fallback visual agar dadu tetap tampil sebelum roll pertama.
- `lastRollerName`: nama roller terakhir; teks `just rolled ...` hanya tampil jika `hasLastRoll()` true.
- `rollResultNotice`: burst angka besar setelah animasi dadu selesai.
- `pendingRollPlayers`: menahan update player dari `RoomStateUpdated` selama roll/dice animation berjalan.
- `effectNotice`: modal informasi spell/trap aktif.
- `pendingEffectPlayers`: menahan update player saat `effectNotice` masih tampil.
- `gameOverSequence`: menahan leaderboard akhir sampai sequence spotlight winner/loser selesai.

## Animasi LP/Score

`score` di public player dipresentasikan sebagai score classic atau LP survival.

Frontend menambahkan field lokal pada player:

- `displayScore`: angka yang sedang ditampilkan.
- `scoreDelta`: delta sementara untuk label `+/-`.
- `scoreAnimationFrame`: id `requestAnimationFrame`.

`syncPlayers()` mempertahankan object player lama agar animasi tidak ter-reset saat state baru datang. `animatePlayerScore()` menjalankan easing dari angka lama ke angka baru.

Aturan timing:

- Perubahan LP/score dari roll ditahan selama `isRolling` atau `isAnimating`, lalu di-apply setelah dice animation dan burst roll result.
- Perubahan LP/score dari kartu ditahan selama modal `effectNotice` tampil, lalu di-apply setelah `closeEffectNotice()` berjalan dan overlay selesai fade-out.

## Modal Efek Kartu

Event `CardEffectUsed` memicu `showEffectNotice(payload, message)`.

Modal menampilkan:

- Jenis kartu (`Spell Activated` atau `Trap Activated`).
- Nama kartu.
- Deskripsi kartu.
- Pemain yang mengaktifkan.
- Target efek jika ada.
- Penanda target acak.
- Detail efek dari `payload.note`.

Auto-close modal efek saat ini 7 detik. Modal juga bisa ditutup manual atau dengan klik backdrop. Setelah modal tertutup, pending update LP/score dari efek kartu baru dijalankan agar terlihat pemain.

## Survival Loadout UI

Saat status `selecting_cards`, frontend menampilkan modal loadout:

- Durasi pilihan: 120 detik.
- Batas pilihan: maksimal 2 spell dan 2 trap.
- Spell dan trap dipisahkan dengan tab.
- Grid kiri menampilkan kartu sesuai tab, 4 kartu per row di desktop besar.
- Klik kartu hanya membuka preview.
- Preview kanan menampilkan kartu terpilih dan tombol `Pilih Kartu Ini` atau `Batalkan Pilihan`.
- Tombol `KUNCI LOADOUT` berada di kanan atas area modal, di dekat timer.
- Jika timer habis, frontend submit otomatis. Backend juga memulai game jika `selection_end_time` sudah lewat.

## Game Over UI

Untuk survival, ketika roll terakhir menyebabkan pemain kalah:

1. Backend tetap broadcast `DiceRolled` dulu agar dadu dapat dianimasikan.
2. Frontend menunda leaderboard dengan `gameOverSequence`.
3. Dice animation selesai.
4. Burst hasil roll tampil.
5. Animasi LP berjalan sampai angka akhir.
6. Spotlight winner dan loser tampil.
7. Leaderboard akhir baru ditampilkan.

## Catatan Teknis Penting

- Server adalah sumber kebenaran. Frontend hanya meminta action dan menerima state baru.
- `RoomRedisRepository::saveRoom()` menyimpan seluruh room sekaligus. Tidak ada locking/transaction pada Redis state, jadi concurrent action pada room yang sama bisa saling overwrite jika terjadi bersamaan.
- Inventory pemain lain tidak dikirim dalam public state.
- Room auto-expire setelah 2 jam sejak terakhir disimpan.
- Host meninggalkan room akan menghapus room dan broadcast `RoomClosed`.
- Non-host meninggalkan room hanya dihapus dari `players`, lalu broadcast `PlayerLeft`.
- `REDIS_CLIENT=predis` dipakai agar aplikasi tidak bergantung pada ekstensi PHP Redis (`Redis` class) di environment lokal yang belum memasang ekstensi tersebut.

## Inkonsistensi/Kandidat Perbaikan

- Route `/room/{code}/cards/skip-trap` aktif, tetapi method `skipTrap` tidak ditemukan di `GameRedisController`.
- `pending_trap_confirmations`, `trap_target_player_id`, dan status `awaiting_trap_confirmation` masih ada di beberapa tempat, tetapi flow konfirmasi trap tidak terlihat lengkap di controller aktif.
- `resources/js/echo.js` menyiapkan Echo Reverb via Vite, tetapi `room.blade.php` juga membuat instance Echo inline dengan konfigurasi pusher/reverb-compatible.
- `sabotaged` dibuat oleh trap, tetapi validasi backend `useCard()` belum terlihat mencegah spell saat pemain punya buff tersebut.
- `blindfold` disebut dalam frontend dan `SurvivalMode`, tetapi tidak ada kartu terkait di `config/cards.php`.
- Kartu legacy `skip_si` dan `multiplier` punya `price`, sedangkan kartu survival dari config tidak punya `price`. Route shop bisa error/berperilaku tidak lengkap untuk kartu survival jika dipakai sebagai shop item.
