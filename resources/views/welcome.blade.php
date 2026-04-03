<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Number Battle - Enter the Arena</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: white; }
        .glass-panel { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 relative overflow-x-hidden bg-gradient-to-br from-indigo-900 via-slate-900 to-black">
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

    <div class="flex flex-col md:flex-row gap-8 z-10 w-full max-w-4xl justify-center">
        <!-- Create Room -->
        <div class="glass-panel p-8 rounded-2xl w-full max-w-md shadow-2xl transition hover:border-pink-500/50">
            <h2 class="text-2xl font-bold mb-6 text-pink-400">Create Room</h2>
            <form action="/room/create" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Your Name</label>
                    <input type="text" name="host_name" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-pink-500" placeholder="e.g. Master">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Room Code/Password</label>
                    <input type="text" name="code" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-pink-500" placeholder="e.g. secret123">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-pink-500/25 transition-all">
                    Create Room
                </button>
            </form>
        </div>

        <!-- Join Room -->
        <div class="glass-panel p-8 rounded-2xl w-full max-w-md shadow-2xl transition hover:border-violet-500/50">
            <h2 class="text-2xl font-bold mb-6 text-violet-400">Join Room</h2>
            <form action="/room/join" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Your Name</label>
                    <input type="text" name="player_name" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500" placeholder="e.g. Challenger">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Room Code/Password</label>
                    <input type="text" name="code" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500" placeholder="e.g. secret123">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-violet-500/25 transition-all">
                    Join Room
                </button>
            </form>
        </div>
    </div>
</body>
</html>
