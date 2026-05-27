<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Rooms - Sang Terpilih</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            color: white;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        [x-cloak] {
            display: none !important;
        }

        .nb-spin {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, .55);
            border-top-color: transparent;
            animation: nb-spin .7s linear infinite;
        }

        @keyframes nb-spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body x-data="roomsPage()" x-init="init()"
    class="min-h-screen bg-gradient-to-br from-indigo-950 via-slate-950 to-black p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <header class="glass-panel rounded-3xl px-6 md:px-8 py-6 mb-6">
            <div>
                <a href="/" class="text-sm text-cyan-300 hover:text-cyan-200 font-semibold">← Kembali</a>
                <h1 class="mt-3 text-4xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-cyan-300">
                    Room Tersedia
                </h1>
                <p class="text-slate-400 mt-2">Pilih room yang masih waiting, lalu isi nama saat join.</p>
            </div>
        </header>

        <div class="flex items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-2 text-sm text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full" :class="connected ? 'bg-emerald-400' : 'bg-slate-500'"></span>
                <span x-text="connected ? 'Realtime aktif' : 'Menghubungkan realtime...'"></span>
            </div>
            <button @click="fetchRooms" :disabled="loading"
                class="inline-flex items-center gap-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 hover:bg-cyan-400/20 px-4 py-2 text-sm font-bold text-cyan-100 disabled:opacity-60">
                <span x-show="loading" class="nb-spin"></span>
                Refresh
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <template x-if="rooms.length === 0 && !loading">
                <div class="md:col-span-2 xl:col-span-3 glass-panel rounded-3xl p-10 text-center">
                    <div class="text-5xl mb-4">🕹️</div>
                    <h2 class="text-2xl font-bold text-white">Belum ada room yang bisa di-join</h2>
                    <p class="text-slate-400 mt-2">Buat room baru dari halaman awal, atau tunggu host lain membuat room.</p>
                </div>
            </template>

            <template x-for="room in rooms" :key="room.code">
                <div class="glass-panel rounded-3xl p-6 border-cyan-400/20 hover:border-cyan-300/50 transition shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.22em] text-cyan-300 font-black"
                                x-text="room.mode === 'survival' ? 'Survival' : 'Classic'"></p>
                            <h3 class="text-3xl font-black text-white mt-1" x-text="room.code"></h3>
                        </div>
                        <span class="rounded-full bg-emerald-400/15 border border-emerald-300/30 px-3 py-1 text-xs font-bold text-emerald-200">
                            Waiting
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-950/45 border border-white/10 p-3">
                            <p class="text-[10px] uppercase tracking-widest text-slate-500">Host</p>
                            <p class="text-lg font-bold text-slate-100 truncate" x-text="room.hostName"></p>
                        </div>
                        <div class="rounded-2xl bg-slate-950/45 border border-white/10 p-3">
                            <p class="text-[10px] uppercase tracking-widest text-slate-500">Players</p>
                            <p class="text-lg font-bold text-slate-100"><span x-text="room.playerCount"></span> joined</p>
                        </div>
                    </div>

                    <button type="button" @click="openJoinModal(room)"
                        class="mt-5 w-full rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 px-5 py-3 text-white font-black shadow-lg inline-flex items-center justify-center gap-2">
                        Gabung Room
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div x-show="showJoinModal" x-cloak @click.self="closeJoinModal"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
        <div class="glass-panel rounded-3xl w-full max-w-md p-6 border border-cyan-400/30 shadow-2xl"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-cyan-300 font-black"
                        x-text="selectedRoom?.mode === 'survival' ? 'Survival' : 'Classic'"></p>
                    <h2 class="text-3xl font-black text-white mt-1">Join <span x-text="selectedRoom?.code"></span></h2>
                    <p class="text-sm text-slate-400 mt-1">Host: <span class="font-bold text-slate-200" x-text="selectedRoom?.hostName"></span></p>
                </div>
                <button type="button" @click="closeJoinModal" class="text-slate-400 hover:text-white">Tutup</button>
            </div>

            <form action="/room/join" method="POST" class="space-y-4" @submit="joiningCode = selectedRoom?.code">
                @csrf
                <input type="hidden" name="code" :value="selectedRoom?.code || ''">
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Nama kamu</label>
                    <input type="text" name="player_name" x-model.trim="playerName" required autofocus
                        class="w-full bg-slate-900/70 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                        placeholder="e.g. Challenger">
                </div>
                <button type="submit" :disabled="!playerName || joiningCode === selectedRoom?.code"
                    class="w-full rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 px-5 py-3 text-white font-black shadow-lg disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center gap-2">
                    <span x-show="joiningCode === selectedRoom?.code" class="nb-spin"></span>
                    <span x-text="joiningCode === selectedRoom?.code ? 'Joining...' : 'Masuk Room'"></span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function roomsPage() {
            return {
                rooms: @json($rooms),
                playerName: '',
                loading: false,
                connected: false,
                joiningCode: null,
                showJoinModal: false,
                selectedRoom: null,

                init() {
                    this.initEcho();
                    this.fetchRooms();
                },

                initEcho() {
                    const isPusher = '{{ config('broadcasting.default') }}' === 'pusher';

                    window.Echo = new Echo({
                        broadcaster: 'pusher',
                        key: isPusher ? '{{ env('PUSHER_APP_KEY') }}' : '{{ env('REVERB_APP_KEY', 'numberbattlekey') }}',
                        cluster: isPusher ? '{{ env('PUSHER_APP_CLUSTER') }}' : 'mt1',
                        wsHost: isPusher ? undefined : window.location.hostname,
                        wsPort: isPusher ? undefined : 8080,
                        wssPort: isPusher ? undefined : 8080,
                        forceTLS: isPusher ? true : false,
                        encrypted: isPusher ? true : false,
                        disableStats: true,
                        enabledTransports: ['ws', 'wss'],
                    });

                    window.Echo.connector.pusher.connection.bind('connected', () => {
                        this.connected = true;
                    });
                    window.Echo.connector.pusher.connection.bind('disconnected', () => {
                        this.connected = false;
                    });

                    window.Echo.channel('rooms')
                        .listen('RoomsUpdated', () => {
                            this.fetchRooms();
                        });
                },

                async fetchRooms() {
                    this.loading = true;
                    try {
                        const res = await fetch('/rooms/list', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        this.rooms = data.rooms || [];
                    } finally {
                        this.loading = false;
                    }
                },

                openJoinModal(room) {
                    this.selectedRoom = room;
                    this.showJoinModal = true;
                    this.$nextTick(() => {
                        document.querySelector('input[name="player_name"]')?.focus();
                    });
                },

                closeJoinModal() {
                    this.showJoinModal = false;
                    this.selectedRoom = null;
                },
            };
        }
    </script>
</body>

</html>
