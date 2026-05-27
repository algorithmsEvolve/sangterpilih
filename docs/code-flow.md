# Code Flow

## Flow Utama

```text
Browser
  -> Laravel route
  -> GameRedisController
  -> RoomRedisRepository get/save room JSON in Redis
  -> broadcast event to room.{code}
  -> Echo listener in room.blade.php
  -> Alpine applyState/update UI
```

Server menyimpan state game, melakukan validasi, menghitung skor/LP, dan mengirim state baru. Frontend tidak menghitung hasil final sebagai sumber kebenaran.

## 1. Landing Page

Route:

```text
GET /
```

Controller closure:

1. Ambil `config('cards.spells')` dan `config('cards.traps')`.
2. Render `resources/views/welcome.blade.php`.
3. User bisa membuat room, membuka daftar room lewat tombol `Gabung Room`, atau melihat katalog kartu.

UI penting:

- Brand tampil sebagai `Sang Terpilih`.
- Asset brand memakai URL raw GitHub `https://raw.githubusercontent.com/algorithmsEvolve/sangterpilih/refs/heads/main/public/images/sang-terpilih-imam.png`.
- Form utama hanya `Buat Room`.
- Mode default adalah `survival`.
- Join manual tidak lagi berada di halaman awal; join dilakukan dari `/rooms`.

## 2. Buat Room

Route:

```text
POST /room/create
```

Method:

```text
GameRedisController::createRoom()
```

Flow:

1. Validasi `host_name`, `code`, dan `mode`.
2. Cek apakah `room:{code}` sudah ada di Redis.
3. Buat `playerId` dengan `uniqid('p_')`.
4. Buat state awal via `RoomRedisRepository::buildInitialRoom()`.
5. Simpan room ke Redis dengan TTL 2 jam.
6. Broadcast `RoomsUpdated` ke channel `rooms` agar halaman daftar room refresh realtime.
7. Simpan `player_id` ke session.
8. Redirect ke `/room/{code}`.

Status awal room:

```text
waiting
```

## 3. Gabung Room

Route:

```text
POST /room/join
```

Method:

```text
GameRedisController::joinRoom()
```

Flow:

1. Validasi `player_name` dan `code`.
2. Ambil room dari Redis.
3. Tolak jika room tidak ditemukan.
4. Tolak jika status bukan `waiting`.
5. Buat `playerId` baru.
6. Tambahkan player ke `$room['players']`.
7. Simpan room.
8. Broadcast `RoomsUpdated` ke channel `rooms` agar jumlah pemain di daftar room refresh realtime.
9. Simpan `player_id` ke session.
10. Broadcast `PlayerJoined`.
11. Broadcast full public state via `RoomStateUpdated`.
12. Redirect ke `/room/{code}`.

## 3A. Room List

Routes:

```text
GET /rooms
GET /rooms/list
```

Flow:

1. `RoomRedisRepository::listAvailableRooms()` membaca key Redis `room:*`.
2. Hanya room dengan status `waiting` yang ditampilkan.
3. Halaman `rooms.blade.php` subscribe ke channel `rooms`.
4. Saat event `RoomsUpdated` diterima, frontend fetch ulang `/rooms/list`.
5. User klik `Gabung Room`.
6. Modal nama pemain muncul.
7. Setelah nama diisi, form POST ke `/room/join`.

## 4. Room View

Route:

```text
GET /room/{code}
```

Method:

```text
GameRedisController::roomView()
```

Flow:

1. Ambil room dari Redis.
2. Jika tidak ada, redirect ke `/`.
3. Ambil `player_id` dari session.
4. Pastikan player ada di room.
5. Build card catalog.
6. Sort public players by id.
7. Ambil inventory pemain aktif sebagai `myInventory`.
8. Pilih view berdasarkan mode:
   - `classic` -> `classicRoom.blade.php`
   - `survival` -> `survivalRoom.blade.php`
9. Kedua view extend `room.blade.php`.

Catatan khusus:

- Jika room survival berada pada status `selecting_cards` dan waktu server sudah melewati `selection_end_time`, controller bisa memanggil `startPlayingFromLoadout()` saat room dibuka agar game tidak macet di loadout.

## 5. Init Frontend Realtime

File:

```text
resources/views/room.blade.php
```

Alpine:

```text
x-data="gameClient()"
x-init="initEcho()"
```

`initEcho()`:

1. Membuat instance `window.Echo`.
2. Subscribe ke `room.{roomCode}`.
3. Register listener:
   - `RoomStateUpdated` -> `applyState(e.state)`
   - `DiceRolled` -> `animateDice(...)`
   - `CardEffectUsed` -> notice dan history
   - `GameOver` -> `prepareGameOver(...)`, spotlight winner/loser, lalu leaderboard
   - `RoomClosed` -> modal lalu redirect
   - `PlayerLeft` -> hapus player lokal
4. Register `beforeunload` untuk mengirim beacon leave room pada host atau room waiting.

## 6. Start Game

Route:

```text
POST /room/{code}/start
```

Method:

```text
GameRedisController::startGame()
```

Flow:

1. Ambil room.
2. Pastikan `player_id` session adalah host.
3. Sort pemain berdasarkan id.
4. Tolak jika pemain kurang dari 2.
5. Pilih mode service:
   - `ClassicMode`
   - `SurvivalMode`
6. Set initial score/LP untuk semua pemain.

Jika mode `survival`:

1. Set status `selecting_cards`.
2. Set `selection_end_time = time() + 120`.
3. Set setiap pemain `has_selected_cards = false`.
4. Simpan room.
5. Broadcast `RoomStateUpdated`.
6. Response JSON state.

Jika mode `classic`:

1. Set status `playing`.
2. Set pemain pertama sebagai `current_turn_player_id`.
3. Reset ronde, turn index, last dice, turn flags.
4. Buat `active_turn_snapshot`.
5. Simpan room.
6. Broadcast `GameStarted`.
7. Broadcast `RoomStateUpdated`.
8. Response JSON state.

## 7. Survival Loadout

Route:

```text
POST /room/{code}/submit-loadout
```

Method:

```text
GameRedisController::submitLoadout()
```

Flow:

1. Pastikan room status `selecting_cards`.
2. Ambil `player_id` session.
3. Ambil input `spells` dan `traps`.
4. Batasi maksimal 2 spell dan 2 trap.
5. Validasi card id ada di catalog, tipe sesuai, dan tidak `not_available`.
6. Simpan inventory pemain.
7. Set `has_selected_cards = true`.
8. Jika semua pemain sudah ready:
   - Set status `playing`.
   - Set pemain pertama sebagai current turn.
   - Reset ronde, turn index, last dice, turn flags.
   - Buat `active_turn_snapshot`.
   - Simpan room.
   - Broadcast `GameStarted`.
9. Jika belum semua ready, simpan room saja.
10. Broadcast `RoomStateUpdated`.
11. Response JSON `myInventory` dan state.

Frontend menghitung timer dari `selectionEndTime` dan `serverTime` yang dikirim backend. Saat timer mencapai 0, frontend memanggil submit loadout otomatis. Backend tetap menjadi sumber kebenaran: jika waktu server sudah melewati `selection_end_time`, submit loadout akan memulai game meskipun belum semua pemain memilih kartu.

UI loadout:

1. Modal loadout muncul untuk setiap pemain.
2. Timer 2 menit tampil dari perhitungan waktu server.
3. Kartu dipisah dalam tab `Spell` dan `Trap`.
4. Area kiri menampilkan grid kartu sesuai tab.
5. Klik kartu hanya membuka preview, tidak langsung memilih.
6. Area kanan menampilkan preview kartu.
7. Tombol `Pilih Kartu Ini` memilih atau membatalkan kartu preview.
8. Tombol `KUNCI LOADOUT` submit pilihan.
9. Jika semua pemain sudah submit, game langsung mulai.
10. Jika timer habis, frontend submit otomatis; backend juga auto-start saat timeout terdeteksi.

## 8. Roll Dice

Route:

```text
POST /room/{code}/roll
```

Method:

```text
GameRedisController::rollDice()
```

Flow:

1. Ambil room.
2. Pastikan session player ada di room.
3. Pastikan status `playing`.
4. Pastikan pemain adalah `current_turn_player_id`.
5. Pastikan belum `has_rolled_this_turn`.
6. Generate dadu `rand(1, 6)`.
7. Jika `turn_multiplier_player_id` adalah pemain ini, kalikan hasil dadu 2 dan reset multiplier.
8. Tandai `active_turn_snapshot['rolled'] = true`.
9. Panggil mode service:

```php
$finalDiceResult = $modeService->processDiceRoll($room, $playerId, $diceResult);
```

10. Jika mode service menset `require_extra_roll`, pemain boleh roll lagi.
11. Jika tidak, set `has_rolled_this_turn = true`.
12. Simpan `last_dice_result` dan `last_roller_name`.
13. Cek game over.
14. Simpan room.
15. Broadcast `DiceRolled`.
16. Broadcast `RoomStateUpdated`.
17. Response JSON dengan dice result, score/LP, state, dan `myInventory`.

Frontend roll:

1. `DiceRolled` memanggil `animateDice()`.
2. `RoomStateUpdated` yang datang saat roll masih berjalan disimpan ke `pendingRollPlayers`.
3. Dadu tetap terlihat walaupun belum ada roll lewat `visibleDiceValues()`.
4. Teks `just rolled ...` hanya muncul saat `hasLastRoll()` true.
5. Setelah animasi dadu selesai, `rollResultNotice` menampilkan angka besar sebentar.
6. Setelah burst angka, `pendingRollPlayers` di-apply sehingga animasi LP/score baru terlihat setelah roll selesai.

## 9. End Turn

Route:

```text
POST /room/{code}/end-turn
```

Method:

```text
GameRedisController::endTurn()
```

Flow:

1. Ambil room.
2. Pastikan session player ada dan sedang mendapat giliran.
3. Pastikan pemain sudah roll.
4. Panggil `advanceTurn($room)`.
5. Simpan room.
6. Broadcast `RoomStateUpdated`.
7. Response success.

`advanceTurn()`:

1. Sort players by id.
2. Jika pemain kurang dari 2, status jadi `finished`.
3. Reset `has_rolled_this_turn` pemain saat ini.
4. Naikkan `turn_index`.
5. Jika melewati jumlah pemain, balik ke index 0 dan `current_round++`.
6. Cek game over.
7. Set player berikutnya sebagai current turn.
8. Reset last dice.
9. Buat turn snapshot baru.

## 10. Use Card

Route:

```text
POST /room/{code}/cards/use
```

Method:

```text
GameRedisController::useCard()
```

Flow umum:

1. Validasi `card_id`.
2. Ambil room dan player session.
3. Pastikan status `playing` atau `awaiting_trap_confirmation`.
4. Pada status `playing`, hanya current player yang bisa memakai kartu, kecuali `skip_si`.
5. Ambil catalog.
6. Pastikan kartu valid dan tersedia.
7. Pastikan kartu ada di inventory pemain.
8. Jika card punya `effect_class`:
   - Resolve class via container.
   - Panggil `apply($room, $playerId, $request->all())`.
   - Jika error, response 400.
   - Ambil `payload`, `advance_turn`, dan `force_dice_roll_event`.
9. Jika tidak punya `effect_class`, jalankan fallback legacy:
   - `multiplier`
   - `skip_si`
10. Hapus kartu dari inventory.
11. Jika `skip_si` atau `advance_turn`, reset trap state dan advance turn.
12. Simpan room.
13. Broadcast `CardEffectUsed` jika ada payload.
14. Broadcast `DiceRolled` jika efek memaksa dice event.
15. Cek game over.
16. Jika belum game over, broadcast `RoomStateUpdated`.
17. Response JSON state dan `myInventory`.

Frontend use card:

1. Inventory/shop/gambler/target modal ditutup setelah request kartu sukses.
2. `CardEffectUsed` memanggil `showEffectNotice()`.
3. Modal efek kartu menampilkan tipe, nama, deskripsi, pemakai, target, dan detail efek.
4. Modal efek auto-close setelah 7 detik, atau bisa ditutup manual.
5. Selama modal efek tampil, update player dari `RoomStateUpdated` disimpan di `pendingEffectPlayers`.
6. Setelah `closeEffectNotice()`, pending players di-apply sehingga animasi LP/score dari efek kartu terlihat setelah modal tertutup.

Visual kartu:

- Shop, inventory, loadout grid, dan loadout preview memakai `cardArtHtml()`.
- Jika kartu punya `image_url`, frontend render `image_url`.
- Jika tidak, frontend render fallback dari `icon` agar art area tidak blank.

## 11. Buy Card

Route:

```text
POST /room/{code}/shop/buy
```

Method:

```text
GameRedisController::buyCard()
```

Flow:

1. Validasi `card_id`.
2. Pastikan room dan player ada.
3. Pastikan status `playing`.
4. Ambil card catalog.
5. Pastikan card valid dan tersedia.
6. Pastikan score cukup untuk `price`.
7. Tambahkan kartu ke inventory.
8. Kurangi score sesuai price.
9. Simpan room.
10. Cek game over.
11. Broadcast state jika belum selesai.
12. Response state dan `myInventory`.

Catatan: kartu survival dari `config/cards.php` saat ini tidak memiliki `price`, sehingga flow shop paling cocok untuk kartu legacy yang punya price.

## 12. Game Over

Method:

```text
GameRedisController::checkAndTriggerGameOver()
```

Flow:

1. Jika status sudah `finished`, return true.
2. Ambil mode service.
3. Jalankan `checkGameOverCondition($room)`.
4. Jika selesai:
   - Set status `finished`.
   - Buat leaderboard dari players sorted score desc.
   - Simpan room.
   - Broadcast `GameOver`.
   - Broadcast `RoomStateUpdated`.
   - Return true.
5. Jika belum selesai, return false.

Frontend game over:

1. Jika `GameOver` datang setelah roll terakhir, frontend tetap membiarkan animasi dadu berjalan.
2. `prepareGameOver()` menyimpan leaderboard, winner, dan loser di `gameOverSequence`.
3. Setelah delay, `revealGameOver()` menampilkan spotlight winner/loser.
4. Fireworks canvas dipicu.
5. Setelah spotlight selesai, status menjadi `finished` dan leaderboard akhir tampil.

## 13. Leave Room

Route:

```text
POST /room/{code}/leave
```

Method:

```text
GameRedisController::leaveRoom()
```

Flow:

1. Ambil `player_id` dari session.
2. Ambil room.
3. Jika player ada:
   - Jika host:
     - Broadcast `RoomClosed`.
     - Delete Redis key room.
     - Broadcast `RoomsUpdated`.
   - Jika non-host:
     - Hapus player dari room.
     - Simpan room.
     - Broadcast `PlayerLeft`.
     - Broadcast `RoomsUpdated`.
4. Hapus `player_id` dari session.
5. Response success.

## 14. Menambah Kartu Baru

Flow teknis:

1. Buat class di `app/Services/Cards/Spells` atau `app/Services/Cards/Traps`.
2. Implement `CardEffectInterface`.
3. Mutasi `$room` sesuai efek.
4. Return `payload.note` agar UI punya pesan.
5. Daftarkan kartu di `config/cards.php` dengan `effect_class`.
6. Pastikan kartu memiliki `icon`. Tambahkan `image_url` hanya jika kartu membutuhkan art SVG/HTML khusus.
7. Jika perlu input tambahan, update frontend `useCard()` di `room.blade.php`.
8. Jika efek perlu diproses saat roll, tambahkan buff handling di `SurvivalMode::processDiceRoll()`.
9. Jika efek mengubah LP/score secara langsung, cukup broadcast state; frontend akan menunda animasi sampai modal efek tertutup.

## 15. Menambah Mode Game Baru

Flow teknis:

1. Buat class di `app/Services/GameModes` yang implement `GameModeInterface`.
2. Tambahkan mapping di `GameRedisController::getModeService()`.
3. Tambahkan opsi mode di form `welcome.blade.php`.
4. Jika label UI berbeda, buat wrapper view seperti `classicRoom.blade.php`.
5. Pastikan `checkGameOverCondition()` benar, karena controller memanggilnya setelah roll, buy card, dan advance turn.
