<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Number Battle - Enter the Arena</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: white; }
        .glass-panel { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
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
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ formLoading: null }" class="min-h-screen flex flex-col items-center justify-center p-4 relative overflow-x-hidden bg-gradient-to-br from-indigo-900 via-slate-900 to-black">
    <!-- Decorative Elements -->
    <div class="absolute top-10 left-10 w-32 h-32 bg-purple-600 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-float"></div>
    <div class="absolute bottom-10 right-10 w-40 h-40 bg-pink-600 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-float" style="animation-delay: 2s;"></div>

    <div class="text-center mb-10 z-10">
        <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500 drop-shadow-lg mb-4">Number Battle</h1>
        <p class="text-xl text-slate-300">Roll the dice, claim the highest score, and become the champion!</p>
    </div>

    @if(session('error'))
        <div class="bg-red-500/20 border border-red-500 text-red-100 px-6 py-3 rounded-xl mb-6 z-10 w-full max-w-md text-center">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-500/20 border border-red-500 text-red-100 px-6 py-3 rounded-xl mb-6 z-10 w-full max-w-md text-left">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Loading overlay: create/join room (server round-trip) -->
    <div x-show="formLoading !== null"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/75 backdrop-blur-md">
        <div class="glass-panel rounded-3xl px-10 py-12 max-w-sm w-full text-center border border-white/10 shadow-[0_0_60px_rgba(139,92,246,0.15)]">
            <div class="nb-spin-ring mx-auto mb-6"></div>
            <p class="text-lg font-semibold text-white tracking-tight" x-text="formLoading === 'create' ? 'Membuat room…' : 'Bergabung ke room…'"></p>
            <p class="text-sm text-slate-400 mt-2">Mohon tunggu sebentar</p>
            <div class="flex justify-center gap-1.5 mt-6">
                <span class="w-2 h-2 rounded-full bg-pink-500 nb-pulse-dot" style="animation-delay: 0ms"></span>
                <span class="w-2 h-2 rounded-full bg-violet-500 nb-pulse-dot" style="animation-delay: 150ms"></span>
                <span class="w-2 h-2 rounded-full bg-indigo-400 nb-pulse-dot" style="animation-delay: 300ms"></span>
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-8 z-10 w-full max-w-4xl justify-center">
        <!-- Create Room -->
        <div class="glass-panel p-8 rounded-2xl w-full max-w-md shadow-2xl transition hover:border-pink-500/50">
            <h2 class="text-2xl font-bold mb-6 text-pink-400">Create Room</h2>
            <form action="/room/create" method="POST" class="space-y-4" @submit="formLoading = 'create'">
                @csrf
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Your Name</label>
                    <input type="text" name="host_name" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-pink-500" placeholder="e.g. Master">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Room Code/Password</label>
                    <input type="text" name="code" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-pink-500" placeholder="e.g. secret123">
                </div>
                <button type="submit" :disabled="formLoading !== null" class="w-full bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-pink-500/25 transition-all disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span x-show="formLoading === 'create'" class="nb-btn-spinner"></span>
                    <span x-text="formLoading === 'create' ? 'Membuat…' : 'Create Room'"></span>
                </button>
            </form>
        </div>

        <!-- Join Room -->
        <div class="glass-panel p-8 rounded-2xl w-full max-w-md shadow-2xl transition hover:border-violet-500/50">
            <h2 class="text-2xl font-bold mb-6 text-violet-400">Join Room</h2>
            <form action="/room/join" method="POST" class="space-y-4" @submit="formLoading = 'join'">
                @csrf
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Your Name</label>
                    <input type="text" name="player_name" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500" placeholder="e.g. Challenger">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Room Code/Password</label>
                    <input type="text" name="code" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500" placeholder="e.g. secret123">
                </div>
                <button type="submit" :disabled="formLoading !== null" class="w-full bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-violet-500/25 transition-all disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span x-show="formLoading === 'join'" class="nb-btn-spinner"></span>
                    <span x-text="formLoading === 'join' ? 'Joining…' : 'Join Room'"></span>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
