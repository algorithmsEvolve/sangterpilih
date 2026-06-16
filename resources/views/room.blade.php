<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room {{ $room->code }} - Sang Terpilih</title>
    @include('room.partials.styles')
</head>

<body class="arena-shell min-h-screen p-3 text-white md:p-6">

    <div x-data="gameClient()" x-init="initEcho()" class="relative z-10 mx-auto w-full max-w-7xl">
        @include('room.partials.overlays')
        @include('room.partials.board')
        @include('room.partials.modals')
    </div>

    <!-- Fireworks Canvas -->
    <canvas id="fireworks"
        class="absolute inset-0 pointer-events-none z-0 opacity-0 transition-opacity duration-1000"></canvas>

    <script>
        @include('room.partials.scripts')
    </script>
</body>

</html>
