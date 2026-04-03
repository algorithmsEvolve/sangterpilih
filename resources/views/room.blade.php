<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room {{ $room->code }} - Number Battle</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Pusher & Echo -->
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: white; }
        .glass-panel { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        /* 3D Dice CSS */
        .scene {
            width: 120px;
            height: 120px;
            perspective: 800px;
            margin: 20px auto;
        }
        .dice {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .dice.rolling {
            animation: spinDice 0.5s linear infinite;
            transition: none;
        }
        @keyframes spinDice {
            0% { transform: rotateX(0deg) rotateY(0deg) rotateZ(0deg); }
            100% { transform: rotateX(360deg) rotateY(720deg) rotateZ(360deg); }
        }
        .dice-face {
            position: absolute;
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #f8fafc, #cbd5e1);
            border: 2px solid #94a3b8;
            border-radius: 20px;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.1), 0 0 10px rgba(0,0,0,0.2);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            padding: 18px;
        }
        .dot {
            width: 22px;
            height: 22px;
            background: radial-gradient(circle at 30% 30%, #ef4444, #991b1b);
            border-radius: 50%;
            box-shadow: inset -2px -2px 4px rgba(0,0,0,0.4), 2px 2px 4px rgba(255,255,255,0.8);
            justify-self: center;
            align-self: center;
        }
        .face-1 { transform: rotateY(0deg) translateZ(60px); }
        .face-2 { transform: rotateY(180deg) translateZ(60px); }
        .face-3 { transform: rotateY(90deg) translateZ(60px); }
        .face-4 { transform: rotateY(-90deg) translateZ(60px); }
        .face-5 { transform: rotateX(90deg) translateZ(60px); }
        .face-6 { transform: rotateX(-90deg) translateZ(60px); }

        .show-1 { transform: rotateY(0deg); }
        .show-2 { transform: rotateY(-180deg); }
        .show-3 { transform: rotateY(-90deg); }
        .show-4 { transform: rotateY(90deg); }
        .show-5 { transform: rotateX(-90deg); }
        .show-6 { transform: rotateX(90deg); }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen flex flex-col p-6 items-center relative overflow-x-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-black">

    <div x-data="gameClient()" x-init="initEcho()" class="w-full max-w-5xl z-10">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-10 w-full glass-panel px-6 py-4 rounded-xl">
            <div>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500">Number Battle</h1>
                <p class="text-slate-400">Room: <span class="text-white font-bold">{{ $room->code }}</span></p>
            </div>
            <div class="text-right flex items-center space-x-4">
                <p class="text-slate-400">You are <span class="font-bold text-violet-400">{{ $currentPlayer->name }}</span></p>
                <button x-show="status === 'waiting'" @click="leaveRoom" class="text-red-400 hover:text-white transition text-xs font-bold border border-red-500/30 px-3 py-1.5 rounded hover:bg-red-500/80 shadow-md">
                    Keluar Room
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Sidebar: Player List -->
            <div class="glass-panel rounded-2xl p-6 h-full flex flex-col">
                <h2 class="text-xl font-bold mb-4 text-pink-400 border-b border-white/10 pb-2">Players</h2>
                <ul class="space-y-3 flex-1 overflow-y-auto">
                    <template x-for="p in players" :key="p.id">
                        <li class="flex justify-between items-center bg-slate-800/50 px-4 py-3 rounded-lg border border-slate-700/50"
                            :class="{'ring-2 ring-violet-500 bg-violet-900/30': status === 'playing' && currentTurn === p.id}">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full" :class="p.id === currentTurn && status === 'playing' ? 'bg-green-400 animate-pulse' : 'bg-slate-600'"></span>
                                <span x-text="p.name" :class="{'font-bold text-violet-300': p.id == currentPlayerId}"></span>
                                <span x-show="p.is_host" class="text-xs bg-pink-500/20 text-pink-300 px-2 py-0.5 rounded ml-2">Host</span>
                            </div>
                            <div class="font-bold text-amber-400" x-text="status !== 'waiting' ? p.score : '-'"></div>
                        </li>
                    </template>
                </ul>

                <!-- Host Controls -->
                <div x-show="isHost && status === 'waiting'" class="mt-6">
                    <button @click="startGame" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 py-3 rounded-xl font-bold text-lg shadow-lg hover:shadow-green-500/25 transition">
                        Start Game
                    </button>
                    <p class="text-xs text-slate-400 text-center mt-2">New players won't be able to join</p>
                </div>
            </div>

            <!-- Main Area -->
            <div class="md:col-span-2 glass-panel rounded-2xl p-8 flex flex-col items-center justify-center min-h-[400px] relative">
                
                <!-- Waiting State -->
                <div x-show="status === 'waiting'" class="text-center animate-pulse">
                    <div class="text-6xl mb-4">⏳</div>
                    <h2 class="text-2xl font-bold text-slate-300 mb-2">Waiting for Host to start...</h2>
                    <p class="text-slate-400">Invite friends with code: <span class="text-white font-bold">{{ $room->code }}</span></p>
                </div>

                <!-- Playing State -->
                <div x-show="status === 'playing'" class="w-full flex flex-col items-center justify-center text-center">
                    
                    <div class="mb-12 mt-4 flex justify-center">
                        <div class="scene">
                            <!-- Alpine classes apply the 3D rotation logic -->
                            <div class="dice" :class="[
                                isAnimating ? 'rolling' : '', 
                                recentDice > 0 && !isAnimating ? 'show-' + recentDice : 'show-1'
                            ]">
                                <div class="dice-face face-1"><div class="dot" style="grid-area: 2/2"></div></div>
                                <div class="dice-face face-2"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                <div class="dice-face face-3"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 2/2"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                <div class="dice-face face-4"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 1/3"></div><div class="dot" style="grid-area: 3/1"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                <div class="dice-face face-5"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 1/3"></div><div class="dot" style="grid-area: 2/2"></div><div class="dot" style="grid-area: 3/1"></div><div class="dot" style="grid-area: 3/3"></div></div>
                                <div class="dice-face face-6"><div class="dot" style="grid-area: 1/1"></div><div class="dot" style="grid-area: 2/1"></div><div class="dot" style="grid-area: 3/1"></div><div class="dot" style="grid-area: 1/3"></div><div class="dot" style="grid-area: 2/3"></div><div class="dot" style="grid-area: 3/3"></div></div>
                            </div>
                        </div>
                    </div>

                    <h2 class="text-3xl font-extrabold mb-2 text-white">
                        <span x-show="currentTurn === currentPlayerId" class="text-green-400">It's Your Turn!</span>
                        <span x-show="currentTurn !== currentPlayerId">Waiting for <span class="text-violet-400" x-text="getCurrentPlayerName()"></span>...</span>
                    </h2>
                    <p class="text-slate-400 mb-8" x-show="recentDice > 0">
                        <span class="font-bold text-white" x-text="lastRollerName"></span> just rolled a <span class="font-bold text-yellow-400" x-text="recentDice"></span>!
                    </p>

                    <button x-show="currentTurn === currentPlayerId" 
                            @click="rollDice" 
                            :disabled="isRolling || isAnimating"
                            class="bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white px-12 py-4 rounded-full font-bold text-2xl shadow-xl hover:shadow-pink-500/50 transition-all transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-text="isRolling ? 'Rolling...' : 'ROLL DICE'"></span>
                    </button>
                </div>

                <!-- Finished State -->
                <div x-show="status === 'finished'" class="w-full text-center" x-cloak>
                    <div class="text-6xl mb-6">🏆</div>
                    <h2 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-yellow-600 mb-6 drop-shadow-lg">Game Over!</h2>
                    
                    <div class="bg-black/40 rounded-xl p-6 border border-yellow-500/30 max-w-md mx-auto">
                        <h3 class="text-xl text-yellow-400 font-bold mb-4">Leaderboard</h3>
                        <ul class="space-y-2 text-left">
                            <template x-for="(bp, index) in leaderboard" :key="index">
                                <li class="flex justify-between items-center py-2 border-b border-white/10 last:border-0" :class="{'text-yellow-300 transform scale-110 font-bold': index === 0}">
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl" x-text="index === 0 ? '👑' : (index === 1 ? '🥈' : (index === 2 ? '🥉' : ''))"></span>
                                        <span class="text-lg" x-text="bp.name"></span>
                                    </div>
                                    <span class="font-black text-xl" x-text="bp.score"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    
                    <a href="#" @click.prevent="leaveRoom" class="inline-block mt-8 text-slate-400 hover:text-white transition underline">Leave Room</a>
                </div>

            </div>
        </div>

        <!-- Kick Modal -->
        <div x-show="showKickModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-red-500/30 rounded-3xl p-8 max-w-sm w-full shadow-[0_0_50px_rgba(239,68,68,0.2)] text-center"
                 x-transition:enter="transition ease-out duration-300 delay-100"
                 x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                <div class="text-7xl mb-6 animate-bounce">⚠️</div>
                <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-pink-500 mb-4">Room Ditutup!</h2>
                <p class="text-slate-300 mb-8 leading-relaxed">Host telah meninggalkan permainan atau menutup Room ini secara sepihak.</p>
                <a href="/" class="block w-full bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-500 hover:to-rose-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-red-500/25 transition">
                    Kembali ke Beranda
                </a>
            </div>
        </div>

    </div>

    <!-- Fireworks Canvas -->
    <canvas id="fireworks" class="absolute inset-0 pointer-events-none z-0 opacity-0 transition-opacity duration-1000"></canvas>

    <script>
        function gameClient() {
            return {
                roomCode: '{{ $room->code }}',
                status: '{{ $room->status }}',
                currentPlayerId: {{ $currentPlayer->id }},
                isHost: {{ $currentPlayer->is_host ? 'true' : 'false' }},
                currentTurn: {{ $room->current_turn_player_id ?? 'null' }},
                players: @json($room->players),
                recentDice: 0,
                lastRollerName: '',
                leaderboard: [],
                isRolling: false,
                isAnimating: false,
                rollingChar: 1,
                queuedTurn: null,
                queuedLeaderboard: null,
                showKickModal: false,

                initEcho() {
                    const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
                    
                    const isPusher = '{{ config('broadcasting.default') }}' === 'pusher';
                    
                    window.Echo = new Echo({
                        broadcaster: 'pusher',
                        key: isPusher ? '{{ env('PUSHER_APP_KEY') }}' : 'numberbattlekey',
                        cluster: isPusher ? '{{ env('PUSHER_APP_CLUSTER') }}' : 'mt1',
                        wsHost: isPusher ? undefined : window.location.hostname,
                        wsPort: isPusher ? undefined : 8080,
                        wssPort: isPusher ? undefined : 8080,
                        forceTLS: isPusher ? true : false,
                        encrypted: isPusher ? true : false,
                        disableStats: true,
                        enabledTransports: ['ws', 'wss'],
                        cluster: isPusher ? '{{ env('PUSHER_APP_CLUSTER') }}' : 'mt1'
                    });

                    window.Echo.channel('room.' + this.roomCode)
                        .listen('PlayerJoined', (e) => {
                            this.players.push(e.player);
                        })
                        .listen('GameStarted', (e) => {
                            this.status = 'playing';
                            this.currentTurn = e.currentTurnPlayerId;
                        })
                        .listen('DiceRolled', (e) => {
                            this.animateDice(e.diceResult, e.playerId, e.score);
                        })
                        .listen('TurnChanged', (e) => {
                            if (this.isAnimating) {
                                this.queuedTurn = e.nextTurnPlayerId;
                            } else {
                                this.currentTurn = e.nextTurnPlayerId;
                            }
                        })
                        .listen('GameOver', (e) => {
                            if (this.isAnimating) {
                                this.queuedLeaderboard = e.leaderboard;
                            } else {
                                this.status = 'finished';
                                this.leaderboard = e.leaderboard;
                                this.triggerFireworks();
                            }
                        })
                        .listen('RoomClosed', (e) => {
                            this.showKickModal = true;
                            setTimeout(() => {
                                window.location.href = '/';
                            }, 5000);
                        })
                        .listen('PlayerLeft', (e) => {
                            this.players = this.players.filter(p => p.id !== e.playerId);
                        });

                    // Tangkap jika tab/browser ditutup
                    window.addEventListener('beforeunload', (e) => {
                        // Host akan menghancurkan room kapansaja. Player biasa hanya dibersihkan jika keluar sebelum start (waiting).
                        if (this.isHost || this.status === 'waiting') {
                            navigator.sendBeacon('/room/' + this.roomCode + '/leave', new URLSearchParams({
                                '_token': csrfToken
                            }));
                        }
                    });
                },

                triggerFireworks() {
                    const canvas = document.getElementById('fireworks');
                    canvas.classList.remove('opacity-0');
                    // Just simple confetti simulation in DOM could also work, but Fireworks helps!
                    // Not fully implementing complex canvas fireworks to keep concise.
                    // A simple background color blink is added:
                    document.body.classList.add('bg-gradient-to-r', 'from-amber-500', 'to-red-600', 'animate-pulse');
                    setTimeout(() => {
                        document.body.classList.remove('animate-pulse');
                    }, 5000);
                },

                getCurrentPlayerName() {
                    const p = this.players.find(p => p.id === this.currentTurn);
                    return p ? p.name : 'Unknown';
                },

                startGame() {
                    fetch('/room/' + this.roomCode + '/start', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                },

                leaveRoom() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    fetch('/room/' + this.roomCode + '/leave', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }).then(() => {
                        window.location.href = '/';
                    }).catch(() => {
                        window.location.href = '/';
                    });
                },

                rollDice() {
                    this.isRolling = true;
                    fetch('/room/' + this.roomCode + '/roll', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    }).then(res => res.json()).then(data => {
                        if (!data.success) {
                            alert(data.error);
                            this.isRolling = false;
                        }
                        // jika success, biarkan WebSocket yg men-trigger animasi dan pergantian giliran
                    }).catch(error => {
                        this.isRolling = false;
                    });
                },

                animateDice(result, pId, newScore) {
                    const pIndex = this.players.findIndex(p => p.id === pId);
                    if (pIndex > -1) {
                        this.lastRollerName = this.players[pIndex].name;
                    }

                    this.isAnimating = true;
                    this.isRolling = false; 
                    this.recentDice = 0; 

                    // Force restart CSS animation
                    this.$nextTick(() => {
                        const diceEl = document.querySelector('.dice');
                        if (diceEl) {
                            diceEl.style.animation = 'none';
                            void diceEl.offsetHeight; /* trigger reflow */
                            diceEl.style.animation = null; 
                        }
                    });

                    setTimeout(() => {
                        this.recentDice = result;
                        this.isAnimating = false;
                        
                        if (pIndex > -1) {
                            this.players[pIndex].score = newScore;
                        }

                        // Flush buffered events
                        if (this.queuedTurn !== null) {
                            this.currentTurn = this.queuedTurn;
                            this.queuedTurn = null;
                        }
                        
                        if (this.queuedLeaderboard !== null) {
                            const board = this.queuedLeaderboard;
                            this.queuedLeaderboard = null;
                            
                            // Jeda 3 detik sebelum menampilkan game over
                            setTimeout(() => {
                                this.status = 'finished';
                                this.leaderboard = board;
                                this.triggerFireworks();
                            }, 3000);
                        }
                    }, 1500);
                }
            };
        }
    </script>
</body>
</html>
