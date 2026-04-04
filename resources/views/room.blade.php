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
        .nb-spin-ring {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #ec4899, #8b5cf6, #6366f1, #ec4899);
            animation: nb-spin 1s linear infinite;
            mask: radial-gradient(farthest-side, transparent calc(100% - 5px), #000 calc(100% - 4px));
            -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 5px), #000 calc(100% - 4px));
        }
        @keyframes nb-spin { to { transform: rotate(360deg); } }
        .nb-btn-spinner {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border-width: 2px;
            border-style: solid;
            border-color: rgba(248, 250, 252, 0.6);
            border-top-color: transparent;
            animation: nb-spin 0.7s linear infinite;
        }
        .nb-pulse-dot { animation: nb-pulse-dot 1.4s ease-in-out infinite; }
        @keyframes nb-pulse-dot { 0%, 100% { opacity: .35; transform: scale(.92); } 50% { opacity: 1; transform: scale(1); } }
        .nb-toast-enter { animation: nb-toast-enter .25s ease-out; }
        @keyframes nb-toast-enter {
            from { opacity: 0; transform: translateY(8px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .nb-card-shell {
            position: relative;
            aspect-ratio: 5 / 8; /* Mentok panjang Yu-Gi-Oh ratio */
            border: 3px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-size: 200% 200%;
            display: flex;
            flex-direction: column;
        }
        .nb-card-shell.trap {
            background: linear-gradient(135deg, #7c2d12 0%, #450a0a 100%);
            border-color: #f87171;
        }
        .nb-card-shell.spell {
            background: linear-gradient(135deg, #064e3b 0%, #022c22 100%);
            border-color: #34d399;
        }
        .nb-card-shell.default {
            background: linear-gradient(180deg, #c6a76c 0%, #9a7b42 100%);
            border-color: #f1d095;
        }
        .nb-card-art {
            border: 2px solid rgba(0, 0, 0, 0.6);
            border-radius: 6px;
            overflow: hidden;
            background: #000;
            margin-bottom: 8px;
            height: 45%; /* Fixed art height to leave space for text */
            flex-shrink: 0;
        }
        .nb-card-art img { width: 100%; height: 100%; object-fit: cover; display: block; opacity: 0.85; transition: 0.3s; }
        .nb-card-shell:hover .nb-card-art img { opacity: 1; transform: scale(1.05); }
        .nb-card-desc-box {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 6px;
            border-radius: 4px;
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 8px;
        }
        .nb-effect-burst { animation: nb-burst 0.7s ease-out; }
    </style>
</head>
<body class="min-h-screen flex flex-col p-6 items-center relative overflow-x-hidden bg-gradient-to-br from-slate-900 via-indigo-900 to-black">

    <div x-data="gameClient()" x-init="initEcho()" class="w-full max-w-5xl z-10">

        <!-- Global loading: start game / leave room (DB + redirect) -->
        <div x-show="loadingStart || loadingLeave"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
            <div class="glass-panel rounded-3xl px-10 py-12 max-w-sm w-full text-center border border-white/10 shadow-[0_0_60px_rgba(139,92,246,0.2)]">
                <div class="nb-spin-ring mx-auto mb-6"></div>
                <p class="text-lg font-semibold text-white tracking-tight"
                   x-text="loadingLeave ? 'Meninggalkan room…' : 'Memulai permainan…'"></p>
                <p class="text-sm text-slate-400 mt-2">Menyimpan ke server</p>
                <div class="flex justify-center gap-1.5 mt-6">
                    <span class="w-2 h-2 rounded-full bg-pink-500 nb-pulse-dot" style="animation-delay: 0ms"></span>
                    <span class="w-2 h-2 rounded-full bg-violet-500 nb-pulse-dot" style="animation-delay: 150ms"></span>
                    <span class="w-2 h-2 rounded-full bg-emerald-400 nb-pulse-dot" style="animation-delay: 300ms"></span>
                </div>
            </div>
        </div>
        
        <!-- Toast -->
        <div x-show="toast.show"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed bottom-6 right-6 z-[120] max-w-sm">
            <div class="nb-toast-enter px-4 py-3 rounded-xl border shadow-2xl backdrop-blur-sm"
                 :class="toast.type === 'error' ? 'bg-red-900/80 border-red-400/50 text-red-100' : 'bg-emerald-900/80 border-emerald-400/50 text-emerald-100'">
                <p class="text-sm font-semibold" x-text="toast.message"></p>
            </div>
        </div>

        <!-- Card Effect Announcement -->
        <div x-show="effectNotice.show" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center pointer-events-none p-4">
            <div class="nb-effect-burst max-w-md w-full rounded-2xl border px-6 py-5 text-center shadow-2xl backdrop-blur-sm"
                 :class="effectNotice.type === 'trap' ? 'bg-red-900/80 border-red-400/60 text-red-100' : 'bg-emerald-900/80 border-emerald-400/60 text-emerald-100'">
                <p class="text-xs uppercase tracking-wide opacity-80" x-text="effectNotice.type === 'trap' ? 'Trap Activated' : 'Spell Activated'"></p>
                <h4 class="text-2xl font-extrabold mt-1" x-text="effectNotice.cardName"></h4>
                <p class="text-sm mt-2" x-text="effectNotice.message"></p>
            </div>
        </div>
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-10 w-full glass-panel px-6 py-4 rounded-xl">
            <div>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500">Number Battle</h1>
                <p class="text-slate-400">Room: <span class="text-white font-bold">{{ $room->code }}</span></p>
                <p class="text-xs text-slate-500 mt-1" x-show="status === 'playing'">Ronde <span class="text-white font-bold" x-text="currentRound"></span> / <span x-text="totalRounds"></span></p>
            </div>
            <div class="text-right flex items-center space-x-4">
                <p class="text-slate-400">You are <span class="font-bold text-violet-400">{{ $currentPlayer->name }}</span></p>
                
                <button x-show="status === 'waiting'" @click="leaveRoom" :disabled="loadingLeave || loadingStart" class="text-red-400 hover:text-white transition text-xs font-bold border border-red-500/30 px-3 py-1.5 rounded hover:bg-red-500/80 shadow-md disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-1.5">
                    <span x-show="loadingLeave" class="nb-btn-spinner"></span>
                    <span x-text="loadingLeave ? 'Keluar…' : 'Keluar Room'"></span>
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
                    <button @click="startGame" :disabled="loadingStart || loadingLeave" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 py-3 rounded-xl font-bold text-lg shadow-lg hover:shadow-green-500/25 transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span x-show="loadingStart" class="nb-btn-spinner"></span>
                        <span x-text="loadingStart ? 'Memulai…' : 'Start Game'"></span>
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
                <div x-show="status === 'playing'" class="w-full flex flex-col items-center justify-center text-center relative">
                    
                    <!-- Compact roll request feedback (DB round-trip) -->
                    <div x-show="isRolling" x-transition
                         class="absolute top-0 left-1/2 -translate-x-1/2 z-10 px-4 py-2 rounded-full bg-slate-900/90 border border-pink-500/30 text-pink-200 text-sm font-medium shadow-lg backdrop-blur-sm flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pink-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-pink-500"></span>
                        </span>
                        Mengirim lemparan ke server…
                    </div>
                    
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

                    <div class="flex gap-3 mb-4">
                        <button @click="showShopModal = true"
                                class="px-5 py-2 rounded-full bg-indigo-500/20 border border-indigo-400/40 hover:bg-indigo-400/25 text-indigo-100 text-sm font-bold transition">
                            Shop
                        </button>
                        <button @click="showInventoryModal = true"
                                class="px-5 py-2 rounded-full bg-slate-500/20 border border-slate-300/30 hover:bg-slate-400/25 text-slate-100 text-sm font-bold transition">
                            Inventory
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 mb-4" x-show="currentTurn === currentPlayerId">
                        Lempar dadu dulu, kalau udah baru pencet <span class="text-emerald-300 font-semibold">Akhiri Giliran</span>.
                    </p>

                    <button x-show="currentTurn === currentPlayerId" 
                            @click="rollDice" 
                            :disabled="isRolling || isAnimating || (me() && me().hasRolledThisTurn)"
                            class="bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white px-12 py-4 rounded-full font-bold text-2xl shadow-xl hover:shadow-pink-500/50 transition-all transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center gap-3">
                        <span x-show="isRolling" class="nb-btn-spinner"></span>
                        <span x-text="isRolling ? 'Rolling…' : 'ROLL DICE'"></span>
                    </button>

                    <button x-show="currentTurn === currentPlayerId"
                            @click="endTurn"
                            :disabled="isEndingTurn || !me() || !me().hasRolledThisTurn"
                            class="mt-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-10 py-3 rounded-full font-bold text-lg shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                        <span x-show="isEndingTurn" class="nb-btn-spinner"></span>
                        <span x-text="isEndingTurn ? 'Mengakhiri…' : 'Akhiri Giliran'"></span>
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
                    
                    <a href="#" @click.prevent="leaveRoom" :class="loadingLeave ? 'opacity-50 pointer-events-none' : ''" class="inline-flex items-center gap-2 mt-8 text-slate-400 hover:text-white transition underline">
                        <span x-show="loadingLeave" class="nb-btn-spinner"></span>
                        <span x-text="loadingLeave ? 'Keluar…' : 'Leave Room'"></span>
                    </a>
                </div>

            </div>
        </div>

        <!-- Shop Modal -->
        <div x-show="showShopModal" x-cloak @click.self="showShopModal = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="glass-panel rounded-2xl w-full max-w-2xl p-6 border border-indigo-400/30"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-2xl font-bold text-indigo-300">Shop Kartu Efek</h3>
                    <button @click="showShopModal = false" class="text-slate-300 hover:text-white">Tutup</button>
                </div>
                <p class="text-sm text-slate-400 mb-4">Belanja pake poin lo. Mau nekat, mau licik, terserah tongkrongan lo.</p>
                <div class="grid sm:grid-cols-2 gap-4">
                    <template x-for="card in cardCatalog" :key="card.id">
                        <div class="nb-card-shell text-slate-100" :class="card.type === 'trap' ? 'trap' : (card.type === 'spell' ? 'spell' : 'default')">
                            <div class="flex items-center justify-between text-[11px] font-black mb-2 px-1">
                                <span class="tracking-wide" x-text="card.name"></span>
                                <span class="text-yellow-300" x-text="card.price + ' pts'"></span>
                            </div>
                            <div class="nb-card-art">
                                <span x-html="card.image_url"></span>
                            </div>
                            <div class="text-[9px] uppercase tracking-widest font-bold opacity-70 mb-1 px-1"
                                 x-text="card.type === 'trap' ? '[ Trap Card ]' : '[ Spell Card ]'"></div>
                            
                            <div class="nb-card-desc-box">
                                <p class="text-[10px] text-slate-200 leading-tight" x-text="card.description"></p>
                            </div>

                            <div class="mt-auto flex gap-1.5 p-1">
                                <button @click="detailCard = card" class="flex-1 py-1.5 rounded bg-black/40 hover:bg-black/60 text-[10px] font-bold transition">LOOK</button>
                                <button @click="buyCard(card.id)"
                                        :disabled="isBuyingCard"
                                        class="flex-1 py-1.5 rounded bg-white/10 hover:bg-white/20 text-[10px] font-bold transition border border-white/10 disabled:opacity-50">
                                    <span x-text="isBuyingCard ? '...' : 'BUY'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Inventory Modal -->
        <div x-show="showInventoryModal" x-cloak @click.self="showInventoryModal = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="glass-panel rounded-2xl w-full max-w-3xl p-6 border border-slate-400/30"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-2xl font-bold text-slate-100">Inventory Lo Doang</h3>
                    <button @click="showInventoryModal = false" class="text-slate-300 hover:text-white">Tutup</button>
                </div>
                <div class="grid sm:grid-cols-2 gap-4 max-h-[70vh] overflow-y-auto pr-1">
                    <template x-if="myInventory.length === 0">
                        <div class="col-span-2 rounded-xl border border-white/10 bg-slate-900/60 p-6 text-center text-slate-400">
                            Inventory lo kosong. Nabung poin dulu, beli kartu, baru rusuh.
                        </div>
                    </template>
                    <template x-for="(cid, index) in myInventory" :key="'mine-' + index">
                        <div class="nb-card-shell text-slate-100" 
                             :class="((cardCatalog.find(c => c.id === cid) || {}).type) === 'trap' ? 'trap' : (((cardCatalog.find(c => c.id === cid) || {}).type) === 'spell' ? 'spell' : 'default')">
                            <div class="flex items-center justify-between text-[11px] font-black mb-2 px-1">
                                <span class="tracking-wide" x-text="(cardCatalog.find(c => c.id === cid) || {}).name || cid"></span>
                            </div>
                            <div class="nb-card-art">
                                <img :src="(cardCatalog.find(c => c.id === cid) || {}).image_url" :alt="cid">
                            </div>
                            <div class="text-[9px] uppercase tracking-widest font-bold opacity-70 mb-1 px-1"
                                 x-text="'[ ' + ((cardCatalog.find(c => c.id === cid) || {}).type || '???').toUpperCase() + ' CARD ]'"></div>
                            
                            <div class="nb-card-desc-box">
                                <p class="text-[10px] text-slate-200 leading-tight" x-text="(cardCatalog.find(c => c.id === cid) || {}).description"></p>
                            </div>

                            <div class="mt-auto flex gap-1.5 p-1">
                                <button @click="detailCard = cardCatalog.find(c => c.id === cid)" class="flex-1 py-1.5 rounded bg-black/40 hover:bg-black/60 text-[10px] font-bold transition">DETAIL</button>
                                <button @click="useCard(cid)"
                                        :disabled="isUsingCard || !canUseCard(cid)"
                                        class="flex-1 py-1.5 rounded bg-emerald-600 hover:bg-emerald-500 text-[10px] font-bold transition disabled:opacity-50">
                                    <span x-text="isUsingCard ? '...' : 'USE'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Card Detail Modal -->
        <div x-show="detailCard" x-cloak @click.self="detailCard = null"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="rounded-2xl w-full max-w-md p-6 border glass-panel"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 :class="detailCard && detailCard.type === 'trap' ? 'border-red-400/40 bg-red-900/20' : 'border-emerald-400/40 bg-emerald-900/20'">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-2xl font-bold" x-text="detailCard?.name"></h3>
                    <button @click="detailCard = null" class="text-slate-300 hover:text-white">Tutup</button>
                </div>
                <p class="text-xs uppercase mb-2" x-text="'Tipe: ' + (detailCard?.type || '-')"></p>
                <div class="nb-card-art h-40 mb-3">
                    <img :src="detailCard?.image_url" :alt="detailCard?.name">
                </div>
                <p class="text-xs text-slate-300 mb-2" x-text="'Gambar: ' + (detailCard?.image || '-')"></p>
                <p class="text-sm text-slate-100 leading-relaxed" x-text="detailCard?.description"></p>
            </div>
        </div>

        <!-- Trap Confirmation Modal -->
        <div x-show="status === 'awaiting_trap_confirmation' && pendingTrapConfirmations.includes(currentPlayerId)" 
             x-cloak 
             class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
            <div class="glass-panel rounded-3xl w-full max-w-md p-8 border border-red-500/50 shadow-[0_0_50px_rgba(239,68,68,0.3)] text-center">
                <div class="text-5xl mb-4">⚠️</div>
                <h3 class="text-3xl font-extrabold text-red-400 mb-2">Trap Opportunity!</h3>
                <p class="text-slate-300 leading-relaxed mb-6">
                    Giliran <span class="font-bold text-white" x-text="getTrapTargetName()"></span> baru aja kelar.
                    Lo punya <span class="font-bold text-red-300">Sekip si</span>. Mau nembak trap sekarang atau biarin dia lanjut?
                </p>
                <div class="flex flex-col gap-3">
                    <button @click="confirmTrapUse" :disabled="isUsingCard" class="w-full py-4 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-lg shadow-lg transition disabled:opacity-50">
                        <span x-text="isUsingCard ? 'Nembak...' : 'PAKAI TRAP SEKARANG!'"></span>
                    </button>
                    <button @click="skipTrapPhase" :disabled="isSkippingTrap" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition disabled:opacity-50">
                        <span x-text="isSkippingTrap ? 'Sabar...' : 'Gak Dulu, Biarin'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Waiting for Others Confirmation -->
        <div x-show="status === 'awaiting_trap_confirmation' && !pendingTrapConfirmations.includes(currentPlayerId)" 
             x-cloak 
             class="fixed inset-0 z-[125] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="glass-panel rounded-2xl p-6 text-center max-w-sm">
                <div class="nb-spin-ring mx-auto mb-4 border-red-500"></div>
                <p class="text-sm font-bold text-slate-400">Nungguin orang lain galau mau pake Trap atau nggak...</p>
                <div class="mt-4 flex justify-center gap-1">
                    <template x-for="pid in pendingTrapConfirmations" :key="pid">
                        <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                    </template>
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
                currentRound: {{ $room->current_round ?? 1 }},
                totalRounds: {{ $room->total_rounds ?? 5 }},
                players: (@json($playersPublic)).map((p) => ({
                    ...p,
                    hasRolledThisTurn: !!p.has_rolled_this_turn,
                })),
                myInventory: @json($myInventory ?? []),
                recentDice: {{ $room->last_dice_result ?? 0 }},
                lastRollerName: @json($room->last_roller_name ?? ''),
                leaderboard: [],
                cardCatalog: @json($cardCatalog ?? []),
                showKickModal: false,
                loadingStart: false,
                loadingLeave: false,
                isRolling: false,
                isAnimating: false,
                isEndingTurn: false,
                isBuyingCard: false,
                isUsingCard: false,
                showShopModal: false,
                showInventoryModal: false,
                detailCard: null,
                pendingTrapConfirmations: @json($room->pending_trap_confirmations ?? []),
                trapTargetPlayerId: {{ $room->trap_target_player_id ?? 'null' }},
                isSkippingTrap: false,
                toast: {
                    show: false,
                    message: '',
                    type: 'success',
                    timeout: null
                },

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
                        .listen('RoomStateUpdated', (e) => {
                            this.applyState(e.state);
                        })
                        .listen('DiceRolled', (e) => {
                            this.animateDice(e.diceResult, e.playerId, e.score);
                        })
                        .listen('CardEffectUsed', (e) => {
                            const p = e.payload || {};
                            this.showEffectNotice(
                                p.cardType || 'spell',
                                p.cardName || 'Kartu',
                                p.cardType === 'trap'
                                    ? (p.usedByPlayerName + ' nembak trap ke ' + (p.targetPlayerName || 'target') + '!')
                                    : (p.usedByPlayerName + ' ngeluarin spell: ' + (p.note || 'efek aktif'))
                            );
                        })
                        .listen('GameOver', (e) => {
                            this.status = 'finished';
                            this.leaderboard = e.leaderboard;
                            this.triggerFireworks();
                        })
                        .listen('RoomClosed', () => {
                            this.showKickModal = true;
                            setTimeout(() => {
                                window.location.href = '/';
                            }, 5000);
                        })
                        .listen('PlayerLeft', (e) => {
                            this.players = this.players.filter(p => p.id !== e.playerId);
                        });

                    window.addEventListener('beforeunload', () => {
                        if (this.isHost || this.status === 'waiting') {
                            navigator.sendBeacon('/room/' + this.roomCode + '/leave', new URLSearchParams({
                                '_token': csrfToken
                            }));
                        }
                    });

                    this.$nextTick(() => this.maybePromptTrapOnTurnChange());
                },

                applyState(state) {
                    if (!state) return;
                    this.status = state.status;
                    this.currentTurn = state.currentTurn;
                    this.currentRound = state.currentRound;
                    this.totalRounds = state.totalRounds;
                    this.turnHasSkip = state.turnHasSkip;
                    this.turnMultiplierPlayerId = state.turnMultiplierPlayerId;
                    this.pendingTrapConfirmations = state.pendingTrapConfirmations ?? [];
                    this.trapTargetPlayerId = state.trapTargetPlayerId;
                    this.players = state.players ?? this.players;
                    this.lastRollerName = state.lastRollerName || '';
                    if (!this.isAnimating) {
                        this.recentDice = state.lastDiceResult || 0;
                    }
                },

                triggerFireworks() {
                    const canvas = document.getElementById('fireworks');
                    canvas.classList.remove('opacity-0');
                    document.body.classList.add('bg-gradient-to-r', 'from-amber-500', 'to-red-600', 'animate-pulse');
                    setTimeout(() => document.body.classList.remove('animate-pulse'), 5000);
                },

                getCurrentPlayerName() {
                    const p = this.players.find(p => p.id === this.currentTurn);
                    return p ? p.name : 'Unknown';
                },

                me() {
                    return this.players.find(p => p.id === this.currentPlayerId) || null;
                },

                canOpenShop() {
                    return this.status === 'playing';
                },

                notify(message, type = 'success') {
                    if (this.toast.timeout) clearTimeout(this.toast.timeout);
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;
                    this.toast.timeout = setTimeout(() => {
                        this.toast.show = false;
                    }, 2600);
                },

                canUseCard(cardId, ownerPlayerId = null) {
                    if (this.status !== 'playing' && this.status !== 'awaiting_trap_confirmation') return false;
                    if (ownerPlayerId !== null && ownerPlayerId !== this.currentPlayerId) return false;
                    const mine = this.me();
                    if (!mine || !(mine.inventory || []).includes(cardId)) return false;

                    if (cardId === 'skip_si') {
                        if (this.currentTurn === this.currentPlayerId && this.status === 'playing') return false;
                        if (this.turnHasSkip) return false;
                        return true;
                    }

                    if (cardId === 'multiplier') {
                        if (this.currentTurn !== this.currentPlayerId) return false;
                        if (this.turnMultiplierPlayerId === this.currentPlayerId) return false;
                        if (!this.myInventory.includes('multiplier')) return false;
                        return true;
                    }

                    return false;
                },

                async postJson(url, body = {}) {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        throw new Error(data.error || 'Terjadi error.');
                    }
                    if (data.state) {
                        this.applyState(data.state);
                    }
                    if (Array.isArray(data.myInventory)) {
                        this.myInventory = data.myInventory;
                    }
                    return data;
                },

                async startGame() {
                    this.loadingStart = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/start');
                        this.notify('Game dimulai. Jangan ngantuk, gas!');
                    } catch (error) {
                        this.notify(error.message || 'Gagal memulai permainan.', 'error');
                    } finally {
                        this.loadingStart = false;
                    }
                },

                leaveRoom() {
                    this.loadingLeave = true;
                    this.postJson('/room/' + this.roomCode + '/leave')
                        .finally(() => {
                            window.location.href = '/';
                        });
                },

                async rollDice() {
                    this.isRolling = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/roll');
                    } catch (error) {
                        this.isRolling = false;
                        this.notify(error.message || 'Gagal melempar dadu.', 'error');
                    }
                },

                async endTurn() {
                    this.isEndingTurn = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/end-turn');
                        this.notify('Giliran kelar. Lanjut korban berikutnya.');
                    } catch (error) {
                        this.notify(error.message || 'Gagal mengakhiri giliran.', 'error');
                    } finally {
                        this.isEndingTurn = false;
                    }
                },

                async buyCard(cardId) {
                    this.isBuyingCard = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/shop/buy', { card_id: cardId });
                        this.notify('Mantap, kartu masuk inventory lo.');
                    } catch (error) {
                        this.notify(error.message || 'Gagal beli kartu.', 'error');
                    } finally {
                        this.isBuyingCard = false;
                    }
                },

                async useCard(cardId) {
                    if (!this.canUseCard(cardId, this.currentPlayerId)) {
                        this.notify('Timing kartu ini belum cocok, sabar dikit.', 'error');
                        return;
                    }
                    this.isUsingCard = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/cards/use', { card_id: cardId });
                        this.notify('Kartu dipakai. Semoga musuh makin kesel.');
                    } catch (error) {
                        this.notify(error.message || 'Gagal pakai kartu.', 'error');
                    } finally {
                        this.isUsingCard = false;
                    }
                },

                cardCount(cardId) {
                    return this.myInventory.filter((c) => c === cardId).length;
                },

                confirmTrapUse() {
                    this.useCard('skip_si');
                },

                getTrapTargetName() {
                    const p = this.players.find(p => p.id === this.trapTargetPlayerId);
                    return p ? p.name : 'Target';
                },

                async skipTrapPhase() {
                    this.isSkippingTrap = true;
                    try {
                        await this.postJson('/room/' + this.roomCode + '/cards/skip-trap');
                    } catch (error) {
                        this.notify(error.message || 'Gagal skip trap.', 'error');
                    } finally {
                        this.isSkippingTrap = false;
                    }
                },

                showEffectNotice(type, cardName, message) {
                    if (this.effectNotice.timeout) clearTimeout(this.effectNotice.timeout);
                    this.effectNotice.type = type;
                    this.effectNotice.cardName = cardName;
                    this.effectNotice.message = message;
                    this.effectNotice.show = true;
                    this.effectNotice.timeout = setTimeout(() => {
                        this.effectNotice.show = false;
                    }, 2400);
                },

                cardTypeClass(type) {
                    return type === 'trap'
                        ? 'border-red-400/40 bg-red-900/20'
                        : 'border-emerald-400/40 bg-emerald-900/20';
                },

                animateDice(result, pId, newScore) {
                    const pIndex = this.players.findIndex(p => p.id === pId);
                    if (pIndex > -1) {
                        this.lastRollerName = this.players[pIndex].name;
                    }

                    this.isAnimating = true;
                    this.isRolling = false;
                    this.recentDice = 0;

                    this.$nextTick(() => {
                        const diceEl = document.querySelector('.dice');
                        if (diceEl) {
                            diceEl.style.animation = 'none';
                            void diceEl.offsetHeight;
                            diceEl.style.animation = null;
                        }
                    });

                    setTimeout(() => {
                        this.recentDice = result;
                        this.isAnimating = false;
                        if (pIndex > -1) {
                            this.players[pIndex].score = newScore;
                        }
                    }, 1200);
                }
            };
        }
    </script>
</body>
</html>
