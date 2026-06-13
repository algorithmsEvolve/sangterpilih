# Chat Summary - Animasi 3D Kartu dan Target Roulette

Tanggal: 2026-06-13
Project: `number-battle` / Sang Terpilih
File utama yang diubah: `resources/views/room.blade.php`

## Konteks Repository

Sebelum melakukan perubahan, instruksi project di `AGENTS.md` sudah dibaca dan diikuti.

Poin penting yang relevan:

- Gameplay aktif menggunakan Redis dan `GameRedisController`.
- UI game utama berada di `resources/views/room.blade.php` dengan Alpine.js.
- Event `CardEffectUsed` digunakan untuk menampilkan efek kartu.
- Server tetap menjadi source of truth untuk gameplay; perubahan yang dilakukan hanya di frontend/visual.
- Untuk perubahan card/game UI, dokumentasi berikut sudah dibaca:
  - `docs/codebase.md`
  - `docs/code-knowledge.md`
  - `docs/code-flow.md`

## Tujuan User

User ingin project memiliki lebih banyak unsur 3D, khususnya:

1. Saat kartu spell/trap diaktifkan, tampil animasi aktivasi kartu 3D yang smooth, keren, terinspirasi dari anime/game seperti Yu-Gi-Oh.
2. Kartu yang flip harus berbentuk kartu aktif yang sedang digunakan, bukan kartu generik.
3. Gambar/art pada kartu harus menyesuaikan kartu aktif.
4. Animasi flip perlu dioptimasi agar tidak terlalu berat.
5. Kartu 3D saat flip perlu memiliki z-index/overflow lebih tinggi sehingga terasa keluar dari wrapper modal.
6. Untuk kartu bertarget acak, perlu ada animasi gacha/rolling 3D untuk menentukan target.

## Perubahan yang Sudah Dilakukan

Semua perubahan dilakukan di:

```text
resources/views/room.blade.php
```

### 1. Animasi Aktivasi Kartu 3D

Modal `CardEffectUsed` lama diubah menjadi sequence aktivasi kartu 3D.

Fitur visual yang ditambahkan:

- Overlay cinematic untuk aktivasi kartu.
- Kartu 3D dengan front/back face.
- Animasi summon dan flip.
- Ring/orbit visual.
- Sigil/particle kecil.
- Glow dan warna berbeda sesuai tipe/palet kartu.
- Info panel tetap menampilkan:
  - jenis kartu (`Spell Activated` / `Trap Activated`),
  - nama kartu,
  - deskripsi,
  - pemain yang mengaktifkan,
  - target efek,
  - detail efek.

State Alpine yang diperluas:

```js
effectNotice: {
    show: false,
    type: 'spell',
    icon: '✦',
    cardName: '',
    cardDescription: '',
    cardArt: '',
    cardStyle: '',
    message: '',
    usedByName: '',
    targetName: '',
    isRandom: false,
    animationKey: 0,
    timeout: null
}
```

`animationKey` digunakan agar animasi 3D bisa re-run setiap kartu aktif.

### 2. Artwork Kartu Sesuai Kartu Aktif

Ditambahkan helper Alpine:

```js
cardVisualPalette(card = {}, type = 'spell')
cardVisualStyle(card = {}, type = 'spell')
effectCardArtHtml(card, type = 'spell')
```

Tujuannya:

- Mengambil palet warna dari field `color` pada card catalog (`config/cards.php`).
- Menerapkan CSS variable seperti:
  - `--nb-effect-primary`
  - `--nb-effect-secondary`
  - `--nb-effect-deep`
  - `--nb-effect-glow`
  - `--nb-card-primary`
  - `--nb-card-secondary`
  - `--nb-card-glow`
- Jika kartu punya `image_url`, kartu 3D memakai gambar itu.
- Jika kartu hanya punya `icon`, dibuat generated artwork khusus berisi:
  - icon besar,
  - nama kartu,
  - gradient sesuai palet kartu,
  - glow/pattern background.

Catatan penting:

- `cardArtHtml()` tetap dipakai untuk inventory/shop/loadout agar ringan.
- `effectCardArtHtml()` khusus untuk kartu besar saat animasi aktivasi.

### 3. Optimasi Animasi 3D Kartu

Animasi awal terasa terlalu berat, kemudian dioptimasi:

- Durasi summon/flip dipendekkan dari sekitar `2.95s` menjadi sekitar `1.65s`.
- Mengurangi penggunaan `filter: blur()` dan `drop-shadow()` berat.
- Mengurangi jumlah ring animasi.
- Particle/sigil dibuat one-shot, bukan infinite.
- Keyframe 3D disederhanakan.
- Menambahkan `will-change` pada elemen yang memang dianimasikan.
- Menambahkan dukungan `prefers-reduced-motion`.

Key CSS penting yang terkait:

```css
.nb-effect-card-3d
.nb-effect-card-face
.nb-effect-card-back
.nb-effect-card-front
.nb-effect-card-art-frame
.nb-card-generated-art
.nb-card-effect-art
.nb-effect-ring
.nb-effect-sigil
@keyframes nb-effect-card-summon
@keyframes nb-effect-card-float
```

### 4. Kartu 3D Keluar dari Wrapper Modal

User ingin kartu 3D saat flip terasa berada di atas wrapper modal dan bisa sedikit keluar dari panel.

Perubahan yang dilakukan:

- Wrapper efek kartu memakai `overflow-visible`.
- `.nb-effect-arena` memakai `overflow: visible`.
- `.nb-effect-card-stage` diberi `z-index: 30` dan `overflow: visible`.
- `.nb-effect-card-3d` diberi `z-index: 60`.
- `.nb-effect-info-panel` diberi `z-index: 20`.
- Margin wrapper modal disesuaikan (`my-8`) agar kartu tidak terlalu mepet viewport.

### 5. Animasi Gacha/Roulette 3D untuk Target Acak

Untuk kartu bertarget acak, sebelumnya target dipilih langsung lalu request dikirim.
Sekarang alurnya menjadi:

1. User klik kartu bertarget acak.
2. Inventory ditutup.
3. Overlay `Random Target Lock` muncul.
4. Target dipilih secara random di frontend dari pemain lain.
5. Animasi roulette/gacha 3D menampilkan kandidat target.
6. Setelah animasi selesai, status berubah menjadi `Target terkunci`.
7. Request kartu baru dikirim ke backend dengan:

```js
{
    target_player_id: selectedPlayer.id,
    is_random: true
}
```

State Alpine yang ditambahkan:

```js
targetRoulette: {
    show: false,
    locked: false,
    cardId: null,
    cardName: '',
    selectedPlayer: null,
    previewPlayers: [],
    animationKey: 0,
    lockTimeout: null,
    timeout: null,
}
```

Helper/method Alpine yang ditambahkan:

```js
targetInitial(player)
shuffledPlayersForRoulette(players, selectedPlayer)
startTargetRoulette(cardId, candidatePlayers)
```

Perubahan pada `useCard(cardId)`:

- Untuk kartu dalam `targetedCards`, alih-alih langsung `executeUseCard()`, sekarang memanggil:

```js
this.startTargetRoulette(cardId, otherPlayers);
```

Kartu bertarget acak saat ini mengikuti daftar existing:

```js
const targetedCards = [
    'curse_heavy_bones', 'blood_siphon',
    'forced_reroll', 'poison_dart', 'karma',
    'reverse_fortune', 'sabotage', 'time_bomb', 'blindfold'
];
```

CSS penting untuk roulette target:

```css
.nb-target-roulette-panel
.nb-target-orbit
.nb-target-card-stack
.nb-target-card
.nb-target-card.is-prev
.nb-target-card.is-active
.nb-target-card.is-next
.nb-target-avatar
.nb-target-name
.nb-target-scanline
@keyframes nb-target-ring
@keyframes nb-target-stack-roll
@keyframes nb-target-winner-pop
@keyframes nb-target-scan
```

Overlay markup ditambahkan sebelum `Card Effect Announcement`.

### 6. Guard Anti-Spam Selama Roulette

Saat roulette target dimulai:

```js
this.isUsingCard = true;
```

Tujuannya agar user tidak memicu kartu lain sebelum target terkunci dan request dikirim.

`executeUseCard()` tetap mengatur `isUsingCard` kembali di `finally`.

## Validasi yang Sudah Dilakukan

Setelah perubahan-perubahan tersebut, validasi berikut sudah dijalankan beberapa kali:

```bash
php number-battle/artisan view:cache
php number-battle/artisan view:clear
```

Hasil terakhir:

- Blade templates cached successfully ✅
- Compiled views cleared successfully ✅
- Diagnostics untuk `resources/views/room.blade.php` tanpa error/warning ✅

## Catatan Teknis Penting untuk Lanjutan

- Perubahan sejauh ini hanya frontend/Blade/Alpine, tidak mengubah backend gameplay.
- Backend tetap memvalidasi `useCard()` melalui `GameRedisController`.
- Target random saat ini dipilih di frontend, lalu dikirim ke backend. Ini sesuai flow existing sebelumnya, hanya sekarang diberi animasi sebelum request.
- Jika ingin random target benar-benar server-authoritative, perlu perubahan backend: request cukup kirim `is_random`, lalu backend memilih target dan payload event mengembalikan target final. Ini belum dilakukan.
- Jika ingin artwork tiap kartu benar-benar berupa gambar unik, perlu menambahkan `image_url` atau asset image untuk tiap kartu di `config/cards.php`. Saat ini kartu tanpa `image_url` memakai generated artwork dari `icon`, `name`, dan `color`.
- Jika ingin lebih banyak 3D di project, area potensial berikutnya:
  - dice arena 3D yang lebih cinematic,
  - player board/card zones,
  - trap set zone,
  - LP damage burst 3D,
  - game over result 3D,
  - loadout card carousel 3D.

## File yang Dibuat

File ringkasan ini dibuat agar sesi bisa dilanjutkan nanti:

```text
CHAT_SUMMARY.md
```
