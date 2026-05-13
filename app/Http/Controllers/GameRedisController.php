<?php

namespace App\Http\Controllers;

use App\Events\CardEffectUsed;
use App\Events\DiceRolled;
use App\Events\GameOver;
use App\Events\GameStarted;
use App\Events\PlayerJoined;
use App\Events\PlayerLeft;
use App\Events\RoomClosed;
use App\Events\RoomStateUpdated;
use App\Repositories\RoomRedisRepository;
use App\Services\GameModes\ClassicMode;
use App\Services\GameModes\SurvivalMode;
use App\Services\GameModes\GameModeInterface;
use Illuminate\Http\Request;

class GameRedisController extends Controller
{
    private const CARD_SKIP = 'skip_si';
    private const CARD_MULTIPLIER = 'multiplier';

    private function getModeService(string $mode): GameModeInterface
    {
        return $mode === 'survival' ? new SurvivalMode() : new ClassicMode();
    }

    private function cardCatalog(): array
    {
        $oldCards = [
            self::CARD_SKIP => [
                'id' => self::CARD_SKIP,
                'name' => 'Sekip si',
                'type' => 'trap',
                'color' => 'red',
                'price' => 5,
                'image' => 'seseorang yang mengacuhkan orang lain',
                'image_url' => '
                    <svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
                        <defs>
                            <linearGradient id="bg" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="#7f1d1d"/>
                            <stop offset="100%" stop-color="#450a0a"/>
                            </linearGradient>
                        </defs>
                        <rect width="512" height="512" fill="url(#bg)"/>
                        <circle cx="170" cy="190" r="64" fill="#fca5a5"/>
                        <rect x="120" y="250" width="100" height="150" rx="26" fill="#ef4444"/>
                        <circle cx="345" cy="180" r="62" fill="#fecaca"/>
                        <rect x="302" y="245" width="92" height="145" rx="24" fill="#6b7280"/>
                        <line x1="66" y1="88" x2="445" y2="420" stroke="#fef2f2" stroke-width="24" stroke-linecap="round"/>
                        <text x="34" y="476" fill="#fff1f2" font-size="44" font-family="Arial, sans-serif" font-weight="700">SEKIP SI</text>
                    </svg>
                ',
                'description' => 'Skip giliran player aktif. Kalo dia udah lempar dadu, poinnya dibalikin kayak belum lempar. Brutal tapi fair.',
            ],
            self::CARD_MULTIPLIER => [
                'id' => self::CARD_MULTIPLIER,
                'name' => 'Multipler',
                'type' => 'spell',
                'color' => 'green',
                'price' => 8,
                'image' => 'tulisan 8x8 6x4 dicoret lalu ada x2',
                'image_url' => '
                    <svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
                        <defs>
                            <linearGradient id="bg2" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="#064e3b"/>
                            <stop offset="100%" stop-color="#022c22"/>
                            </linearGradient>
                        </defs>
                        <rect width="512" height="512" fill="url(#bg2)"/>
                        <text x="64" y="180" fill="#a7f3d0" font-size="84" font-family="Arial, sans-serif" font-weight="700">8x8</text>
                        <text x="64" y="272" fill="#a7f3d0" font-size="84" font-family="Arial, sans-serif" font-weight="700">6x4</text>
                        <line x1="52" y1="120" x2="340" y2="320" stroke="#ef4444" stroke-width="16" stroke-linecap="round"/>
                        <text x="286" y="262" fill="#ecfeff" font-size="118" font-family="Arial, sans-serif" font-weight="900">x2</text>
                        <text x="30" y="476" fill="#d1fae5" font-size="44" font-family="Arial, sans-serif" font-weight="700">MULTIPLER</text>
                    </svg>
                ',
                'description' => 'Aktifin di giliran lo, sebelum lempar. Hasil dadu lo jadi x2. Gaspol!',
            ],
        ];

        $spells = config('cards.spells', []);
        $traps = config('cards.traps', []);

        $merged = array_merge($oldCards, $spells, $traps);

        return array_map(function ($card) {
            $card['not_available'] = (int) ($card['not_available'] ?? 0);
            return $card;
        }, $merged);
    }

    private function normalizeInventory(?array $inventory): array
    {
        return array_values(array_filter($inventory ?? [], function ($cardId) {
            return is_string($cardId);
        }));
    }

    private function buildRoomState(array $room, bool $includeInventories = false): array
    {
        $players = array_values($room['players']);
        $mappedPlayers = array_map(function ($p) use ($includeInventories) {
            return [
                'id' => $p['id'],
                'name' => $p['name'],
                'score' => $p['score'],
                'is_host' => $p['is_host'],
                'hasRolledThisTurn' => $p['has_rolled_this_turn'],
                'has_selected_cards' => $p['has_selected_cards'] ?? false,
                'active_buffs' => $p['active_buffs'] ?? [],
                'inventory' => $includeInventories ? $this->normalizeInventory($p['inventory']) : [],
            ];
        }, $players);

        // Sort by ID to keep order predictable (like orderBy('id') in DB)
        usort($mappedPlayers, function($a, $b) {
            return strcmp($a['id'], $b['id']);
        });

        return [
            'mode' => $room['mode'] ?? 'classic',
            'status' => $room['status'],
            'currentTurn' => $room['current_turn_player_id'],
            'currentRound' => $room['current_round'],
            'totalRounds' => $room['total_rounds'],
            'turnHasSkip' => $room['turn_has_skip'],
            'turnMultiplierPlayerId' => $room['turn_multiplier_player_id'],
            'lastDiceResult' => $room['last_dice_result'],
            'lastRollerName' => $room['last_roller_name'],
            'pendingTrapConfirmations' => $room['pending_trap_confirmations'] ?? [],
            'trapTargetPlayerId' => $room['trap_target_player_id'],
            'selectionEndTime' => $room['selection_end_time'] ?? null,
            'players' => $mappedPlayers,
        ];
    }

    private function broadcastState(string $code): void
    {
        $room = RoomRedisRepository::getRoom($code);
        if ($room) {
            broadcast(new RoomStateUpdated($code, $this->buildRoomState($room, false)));
        }
    }

    private function startTurnSnapshot(array &$room, array $activePlayer): void
    {
        $room['active_turn_snapshot'] = [
            'player_id' => $activePlayer['id'],
            'start_score' => $activePlayer['score'],
            'rolled' => false,
        ];
        $room['turn_has_skip'] = false;
        $room['turn_multiplier_player_id'] = null;
        $room['pending_trap_confirmations'] = [];
        $room['trap_target_player_id'] = null;
    }

    private function advanceTurn(array &$room): ?array
    {
        $players = array_values($room['players']);
        usort($players, function($a, $b) { return strcmp($a['id'], $b['id']); });
        
        if (count($players) < 2) {
            $room['status'] = 'finished';
            return null;
        }

        $currentPlayerId = $room['current_turn_player_id'];
        if (isset($room['players'][$currentPlayerId])) {
            $room['players'][$currentPlayerId]['has_rolled_this_turn'] = false;
        }

        $nextTurnIndex = $room['turn_index'] + 1;
        $isNextRound = $nextTurnIndex >= count($players);

        if ($isNextRound) {
            $nextTurnIndex = 0;
            $room['current_round']++;
        }

        if ($this->checkAndTriggerGameOver($room, $room['code'])) {
            return null;
        }

        $nextPlayer = $players[$nextTurnIndex];
        $room['turn_index'] = $nextTurnIndex;
        $room['current_turn_player_id'] = $nextPlayer['id'];
        $room['last_dice_result'] = null;
        $room['last_roller_name'] = null;

        $this->startTurnSnapshot($room, $nextPlayer);
        return ['nextPlayerId' => $nextPlayer['id']];
    }

    private function checkAndTriggerGameOver(array &$room, string $code): bool
    {
        if (($room['status'] ?? '') === 'finished') return true;

        $modeService = $this->getModeService($room['mode'] ?? 'classic');
        if ($modeService->checkGameOverCondition($room)) {
            $room['status'] = 'finished';
            $leaderboard = array_values($room['players']);
            usort($leaderboard, function($a, $b) { return $b['score'] <=> $a['score']; });

            RoomRedisRepository::saveRoom($code, $room);
            broadcast(new GameOver($code, $leaderboard));
            $this->broadcastState($code);
            return true;
        }
        return false;
    }

    public function createRoom(Request $request)
    {
        $request->validate([
            'host_name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'mode' => 'nullable|string|in:classic,survival',
        ]);

        if (RoomRedisRepository::getRoom($request->code)) {
            return back()->with('error', 'Room code already in use!');
        }

        $mode = $request->mode ?? 'classic';
        $playerId = uniqid('p_');
        $room = RoomRedisRepository::buildInitialRoom($request->code, $playerId, $request->host_name, $mode);
        
        RoomRedisRepository::saveRoom($request->code, $room);
        session(['player_id' => $playerId]);

        return redirect('/room/' . $room['code']);
    }

    public function joinRoom(Request $request)
    {
        $request->validate([
            'player_name' => 'required|string|max:255',
            'code' => 'required|string',
        ]);

        $room = RoomRedisRepository::getRoom($request->code);
        if (!$room) {
            return back()->with('error', 'Room tidak ditemukan!');
        }

        if ($room['status'] !== 'waiting') {
            return back()->with('error', 'Room sudah mulai bermain!');
        }

        $playerId = uniqid('p_');
        $newPlayer = [
            'id' => $playerId,
            'name' => $request->player_name,
            'is_host' => false,
            'score' => 0,
            'has_rolled_this_turn' => false,
            'inventory' => [],
        ];
        
        $room['players'][$playerId] = $newPlayer;
        RoomRedisRepository::saveRoom($request->code, $room);

        session(['player_id' => $playerId]);

        broadcast(new PlayerJoined($room['code'], $newPlayer));
        // Paksa paketan data ruang secara keseluruhan ditransfer ke Host agar layout nama pemain langsung sinkron:
        $this->broadcastState($request->code);

        return redirect('/room/' . $room['code']);
    }

    public function roomView($code)
    {
        $room = RoomRedisRepository::getRoom($code);
        if (!$room) {
            return redirect('/')->with('error', 'Room tidak ditemukan atau sudah kadaluarsa.');
        }

        $currentPlayerId = session('player_id');
        if (!$currentPlayerId || !isset($room['players'][$currentPlayerId])) {
            return redirect('/')->with('error', 'Silakan join/create room terlebih dahulu.');
        }

        $currentPlayerObj = (object) $room['players'][$currentPlayerId]; // For Blade compatibility if it expects object properties, though arrays are fine. Let's send it as array if Blade allows, or object.
        $currentPlayer = json_decode(json_encode($room['players'][$currentPlayerId])); // Quick cast to object for blade compatibility

        $cardCatalog = array_values($this->cardCatalog());
        
        $playersVal = array_values($room['players']);
        usort($playersVal, function($a, $b) { return strcmp($a['id'], $b['id']); });
        
        $playersPublic = array_map(function ($p) {
            return [
                'id' => $p['id'],
                'name' => $p['name'],
                'score' => $p['score'],
                'is_host' => $p['is_host'],
                'has_rolled_this_turn' => $p['has_rolled_this_turn'],
                'active_buffs' => $p['active_buffs'] ?? [],
            ];
        }, $playersVal);

        $myInventory = $this->normalizeInventory($room['players'][$currentPlayerId]['inventory'] ?? []);

        // We convert $room to object recursively so $room->code in blade still works.
        $roomObj = json_decode(json_encode($room));

        $mode = $room['mode'] ?? 'classic';
        $viewName = $mode === 'survival' ? 'survivalRoom' : 'classicRoom';

        return view($viewName, [
            'room' => $roomObj, 
            'currentPlayer' => $currentPlayer, 
            'cardCatalog' => $cardCatalog, 
            'playersPublic' => $playersPublic, 
            'myInventory' => $myInventory
        ]);
    }

    public function startGame($code)
    {
        $room = RoomRedisRepository::getRoom($code);
        if (!$room) return response()->json(['error' => 'Room tidak ditemukan.'], 404);

        $currentPlayerId = session('player_id');
        $currentPlayer = $room['players'][$currentPlayerId] ?? null;

        if (!$currentPlayer || !$currentPlayer['is_host']) {
            return response()->json(['error' => 'Cuma host yang bisa start game.'], 403);
        }

        $players = array_values($room['players']);
        usort($players, function($a, $b) { return strcmp($a['id'], $b['id']); });

        if (count($players) < 2) {
            return response()->json(['error' => 'Butuh minimal 2 pemain!'], 400);
        }

        $firstPlayer = $players[0];
        
        $modeService = $this->getModeService($room['mode'] ?? 'classic');
        $initialScore = $modeService->getInitialScore(count($players));
        
        foreach ($room['players'] as $pId => $p) {
            $room['players'][$pId]['score'] = $initialScore;
        }

        if ($room['mode'] === 'survival') {
            $room['status'] = 'selecting_cards';
            $room['selection_end_time'] = time() + 30;
            foreach ($room['players'] as $pId => $p) {
                $room['players'][$pId]['has_selected_cards'] = false;
            }
            RoomRedisRepository::saveRoom($code, $room);
            $this->broadcastState($room['code']);
            return response()->json([
                'success' => true,
                'state' => $this->buildRoomState($room, false),
            ]);
        }

        $room['status'] = 'playing';
        $room['current_turn_player_id'] = $firstPlayer['id'];
        $room['current_round'] = 1;
        $room['total_rounds'] = max(5, $room['total_rounds'] ?? 5);
        $room['turn_index'] = 0;
        $room['turn_has_skip'] = false;
        $room['turn_multiplier_player_id'] = null;
        $room['last_dice_result'] = null;
        $room['last_roller_name'] = null;

        $this->startTurnSnapshot($room, $firstPlayer);
        RoomRedisRepository::saveRoom($code, $room);

        broadcast(new GameStarted($room['code'], $room['current_turn_player_id']));
        $this->broadcastState($room['code']);

        return response()->json([
            'success' => true,
            'state' => $this->buildRoomState($room, false),
            'myInventory' => $this->normalizeInventory($currentPlayer['inventory']),
        ]);
    }

    public function submitLoadout(Request $request, $code)
    {
        $room = RoomRedisRepository::getRoom($code);
        if (!$room) return response()->json(['error' => 'Room tidak ditemukan.'], 404);

        if ($room['status'] !== 'selecting_cards') {
            return response()->json(['error' => 'Bukan saatnya memilih kartu.'], 400);
        }

        $playerId = session('player_id');
        if (!isset($room['players'][$playerId])) {
            return response()->json(['error' => 'Player tidak ditemukan.'], 404);
        }

        $spells = $request->input('spells', []);
        $traps = $request->input('traps', []);
        $catalog = $this->cardCatalog();

        // Validate max 2 spells, 2 trap. We can just take the first 2 and 2 if more are provided.
        $spells = array_slice($spells, 0, 2);
        $traps = array_slice($traps, 0, 2);
        $spells = array_values(array_filter($spells, function ($cardId) use ($catalog) {
            return isset($catalog[$cardId]) && ($catalog[$cardId]['type'] ?? null) === 'spell' && empty($catalog[$cardId]['not_available']);
        }));
        $traps = array_values(array_filter($traps, function ($cardId) use ($catalog) {
            return isset($catalog[$cardId]) && ($catalog[$cardId]['type'] ?? null) === 'trap' && empty($catalog[$cardId]['not_available']);
        }));
        $inventory = array_merge($spells, $traps);

        $room['players'][$playerId]['inventory'] = $inventory;
        $room['players'][$playerId]['has_selected_cards'] = true;

        // Check if all players are ready
        $allReady = true;
        foreach ($room['players'] as $p) {
            if (empty($p['has_selected_cards'])) {
                $allReady = false;
                break;
            }
        }

        // If everyone is ready, transition to playing
        if ($allReady) {
            $players = array_values($room['players']);
            usort($players, function($a, $b) { return strcmp($a['id'], $b['id']); });
            
            $firstPlayer = $players[0];
            $room['status'] = 'playing';
            $room['current_turn_player_id'] = $firstPlayer['id'];
            $room['current_round'] = 1;
            $room['total_rounds'] = max(5, $room['total_rounds'] ?? 5);
            $room['turn_index'] = 0;
            $room['turn_has_skip'] = false;
            $room['turn_multiplier_player_id'] = null;
            $room['last_dice_result'] = null;
            $room['last_roller_name'] = null;
    
            $this->startTurnSnapshot($room, $firstPlayer);
            RoomRedisRepository::saveRoom($code, $room);

            broadcast(new GameStarted($room['code'], $room['current_turn_player_id']));
        } else {
            RoomRedisRepository::saveRoom($code, $room);
        }

        $this->broadcastState($room['code']);

        return response()->json([
            'success' => true,
            'myInventory' => $this->normalizeInventory($room['players'][$playerId]['inventory']),
            'state' => $this->buildRoomState($room, false),
        ]);
    }

    public function rollDice(Request $request, $code)
    {
        $room = RoomRedisRepository::getRoom($code);
        if (!$room) return response()->json(['error' => 'Room tidak ditemukan.'], 404);

        $playerId = session('player_id');
        if (!isset($room['players'][$playerId])) {
            return response()->json(['error' => 'Player tidak ditemukan di room ini.'], 404);
        }

        if ($room['status'] !== 'playing') {
            return response()->json(['error' => 'Game is not in playing state'], 400);
        }

        if ($room['current_turn_player_id'] !== $playerId) {
            return response()->json(['error' => 'Bukan giliranmu!'], 400);
        }

        if ($room['players'][$playerId]['has_rolled_this_turn']) {
            return response()->json(['error' => 'Kamu sudah melempar dadu!'], 400);
        }

        $diceResult = rand(1, 6);
        if ($room['turn_multiplier_player_id'] === $playerId) {
            $diceResult *= 2;
            $room['turn_multiplier_player_id'] = null;
        }

        $room['active_turn_snapshot']['rolled'] = true;
        
        $modeService = $this->getModeService($room['mode'] ?? 'classic');
        $finalDiceResult = $modeService->processDiceRoll($room, $playerId, $diceResult);

        if (!empty($room['players'][$playerId]['require_extra_roll'])) {
            $room['players'][$playerId]['has_rolled_this_turn'] = false;
        } else {
            $room['players'][$playerId]['has_rolled_this_turn'] = true;
        }
        
        $room['last_dice_result'] = $finalDiceResult;
        $room['last_roller_name'] = $room['players'][$playerId]['name'];
        
        if ($this->checkAndTriggerGameOver($room, $code)) {
            return response()->json([
                'success' => true,
                'diceResult' => $finalDiceResult,
                'score' => $room['players'][$playerId]['score'],
                'state' => $this->buildRoomState($room, false),
                'myInventory' => $this->normalizeInventory($room['players'][$playerId]['inventory']),
            ]);
        }

        RoomRedisRepository::saveRoom($code, $room);

        broadcast(new DiceRolled($room['code'], $playerId, $diceResult, $room['players'][$playerId]['score']));
        $this->broadcastState($room['code']);

        return response()->json([
            'success' => true,
            'diceResult' => $finalDiceResult,
            'score' => $room['players'][$playerId]['score'],
            'state' => $this->buildRoomState($room, false),
            'myInventory' => $this->normalizeInventory($room['players'][$playerId]['inventory']),
        ]);
    }

    public function endTurn($code)
    {
        $room = RoomRedisRepository::getRoom($code);
        if (!$room) return response()->json(['error' => 'Room tidak ditemukan.'], 404);

        $playerId = session('player_id');
        if (!isset($room['players'][$playerId]) || $room['current_turn_player_id'] !== $playerId) {
            return response()->json(['error' => 'Bukan giliranmu!'], 400);
        }

        if (!$room['players'][$playerId]['has_rolled_this_turn']) {
            return response()->json(['error' => 'Lempar dadu dulu, baru akhiri giliran.'], 400);
        }

        $this->advanceTurn($room);
        RoomRedisRepository::saveRoom($code, $room);
        $this->broadcastState($code);

        return response()->json(['success' => true]);
    }

    public function buyCard(Request $request, $code)
    {
        $request->validate([
            'card_id' => 'required|string',
        ]);

        $room = RoomRedisRepository::getRoom($code);
        if (!$room) return response()->json(['error' => 'Room tidak ditemukan.'], 404);

        $playerId = session('player_id');
        if (!isset($room['players'][$playerId])) {
            return response()->json(['error' => 'Player tidak ditemukan.'], 404);
        }

        if ($room['status'] !== 'playing') {
            return response()->json(['error' => 'Shop cuma aktif saat game berjalan.'], 400);
        }

        $catalog = $this->cardCatalog();
        $cardId = $request->card_id;

        if (!isset($catalog[$cardId])) {
            return response()->json(['error' => 'Kartu tidak valid.'], 400);
        }

        $card = $catalog[$cardId];
        if (!empty($card['not_available'])) {
            return response()->json(['error' => 'Kartu ini belum tersedia.'], 400);
        }

        if ($room['players'][$playerId]['score'] < $card['price']) {
            return response()->json(['error' => 'Poin lo belum cukup buat beli kartu ini.'], 400);
        }

        $room['players'][$playerId]['inventory'][] = $cardId;
        $room['players'][$playerId]['score'] -= $card['price'];
        
        RoomRedisRepository::saveRoom($code, $room);
        
        if (!$this->checkAndTriggerGameOver($room, $code)) {
            $this->broadcastState($code);
        }

        return response()->json([
            'success' => true,
            'state' => $this->buildRoomState($room, false),
            'myInventory' => $this->normalizeInventory($room['players'][$playerId]['inventory']),
        ]);
    }

    public function useCard(Request $request, $code)
    {
        $request->validate([
            'card_id' => 'required|string',
        ]);

        $room = RoomRedisRepository::getRoom($code);
        if (!$room) return response()->json(['error' => 'Room tidak ditemukan.'], 404);

        $playerId = session('player_id');
        if (!isset($room['players'][$playerId])) {
            return response()->json(['error' => 'Player tidak ditemukan.'], 404);
        }

        if ($room['status'] !== 'playing' && $room['status'] !== 'awaiting_trap_confirmation') {
            return response()->json(['error' => 'Kartu cuma bisa dipakai saat game berjalan.'], 400);
        }

        if ($room['status'] === 'playing' && $room['current_turn_player_id'] !== $playerId && $request->card_id !== 'skip_si') {
            return response()->json(['error' => 'Sabar bos, ini bukan giliranmu! Kartu cuma bisa dipakai saat giliranmu.'], 400);
        }

        $catalog = $this->cardCatalog();
        $cardId = $request->card_id;

        if (!isset($catalog[$cardId])) {
            return response()->json(['error' => 'Kartu tidak valid.'], 400);
        }

        if (!empty($catalog[$cardId]['not_available'])) {
            return response()->json(['error' => 'Kartu ini belum tersedia.'], 400);
        }

        $inventory = $room['players'][$playerId]['inventory'] ?? [];
        $cardIndex = array_search($cardId, $inventory, true);
        
        if ($cardIndex === false) {
            return response()->json(['error' => 'Kartu ini gak ada di inventory lo.'], 400);
        }

        $effectClass = $catalog[$cardId]['effect_class'] ?? null;
        $effectPayload = null;
        $playerName = $room['players'][$playerId]['name'];
        $advanceTurn = false;
        $forceDiceRollEvent = null;

        if ($effectClass && class_exists($effectClass)) {
            $effect = app($effectClass);
            $result = $effect->apply($room, $playerId, $request->all());

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], 400);
            }

            $effectPayload = $result['payload'] ?? null;
            if ($effectPayload) {
                // Ensure card details are filled if missing
                $effectPayload['cardId'] = $cardId;
                $effectPayload['cardName'] = $catalog[$cardId]['name'];
                $effectPayload['cardType'] = $catalog[$cardId]['type'];
                if ($request->has('is_random') && $request->boolean('is_random')) {
                    $effectPayload['isRandom'] = true;
                }
            }
            $advanceTurn = !empty($result['advance_turn']);
            $forceDiceRollEvent = $result['force_dice_roll_event'] ?? null;
        } else {
            // Fallback for hardcoded cards
            if ($cardId === self::CARD_MULTIPLIER) {
                if ($room['current_turn_player_id'] !== $playerId) {
                    return response()->json(['error' => 'Spell multiplier cuma bisa dipakai saat giliran lo.'], 400);
                }

                if ($room['turn_multiplier_player_id'] === $playerId) {
                    return response()->json(['error' => 'Multiplier lo udah aktif di giliran ini.'], 400);
                }

                if ($room['players'][$playerId]['has_rolled_this_turn']) {
                    $startScore = $room['active_turn_snapshot']['start_score'] ?? $room['players'][$playerId]['score'];
                    $turnGain = max(0, $room['players'][$playerId]['score'] - $startScore);
                    
                    if ($turnGain <= 0) {
                        return response()->json(['error' => 'Belum ada hasil roll yang bisa digandain.'], 400);
                    }
                    
                    $room['players'][$playerId]['score'] = $startScore + ($turnGain * 2);
                    if ($room['last_dice_result']) {
                        $room['last_dice_result'] *= 2;
                    }
                    $room['turn_multiplier_player_id'] = null;
                    
                    $effectPayload = [
                        'cardId' => $cardId,
                        'cardName' => $catalog[$cardId]['name'],
                        'cardType' => $catalog[$cardId]['type'],
                        'usedByPlayerId' => $playerId,
                        'usedByPlayerName' => $playerName,
                        'targetPlayerId' => $playerId,
                        'targetPlayerName' => $playerName,
                        'note' => $playerName . ' nge-boost hasil dadu jadi x2.',
                    ];
                } else {
                    $room['turn_multiplier_player_id'] = $playerId;
                    $effectPayload = [
                        'cardId' => $cardId,
                        'cardName' => $catalog[$cardId]['name'],
                        'cardType' => $catalog[$cardId]['type'],
                        'usedByPlayerId' => $playerId,
                        'usedByPlayerName' => $playerName,
                        'targetPlayerId' => $playerId,
                        'targetPlayerName' => $playerName,
                        'note' => $playerName . ' siapin multiplier. Roll berikutnya bakal x2.',
                    ];
                }
            } elseif ($cardId === self::CARD_SKIP) {
                if ($room['current_turn_player_id'] === $playerId) {
                    return response()->json(['error' => 'Trap skip dipakai buat ngerjain orang lain, bukan diri sendiri.'], 400);
                }

                if ($room['turn_has_skip']) {
                    return response()->json(['error' => 'Skip udah kepake di giliran ini, gak bisa dobel.'], 400);
                }

                $targetId = $room['current_turn_player_id'];
                $targetName = null;
                
                if (isset($room['players'][$targetId])) {
                    $targetName = $room['players'][$targetId]['name'];
                    $startScore = $room['active_turn_snapshot']['start_score'] ?? null;
                    
                    if ($room['active_turn_snapshot']['player_id'] === $targetId && $startScore !== null) {
                        $room['players'][$targetId]['score'] = $startScore;
                        $room['players'][$targetId]['has_rolled_this_turn'] = false;
                        $room['last_dice_result'] = null;
                        $room['last_roller_name'] = null;
                    }
                }

                $room['turn_has_skip'] = true;
                $effectPayload = [
                    'cardId' => $cardId,
                    'cardName' => $catalog[$cardId]['name'],
                    'cardType' => $catalog[$cardId]['type'],
                    'usedByPlayerId' => $playerId,
                    'usedByPlayerName' => $playerName,
                    'targetPlayerId' => $targetId,
                    'targetPlayerName' => $targetName,
                    'note' => $playerName . ' ngeskip giliran ' . ($targetName ?? 'target') . '. Sadis!',
                ];
            } else {
                return response()->json(['error' => 'Efek kartu ini belum siap dipakai!'], 400);
            }
        }

        // Remove used card from inventory (fresh read: effects may append cards first)
        $invAfter = $room['players'][$playerId]['inventory'] ?? [];
        $removeIdx = array_search($cardId, $invAfter, true);
        if ($removeIdx !== false) {
            unset($invAfter[$removeIdx]);
            $room['players'][$playerId]['inventory'] = array_values($invAfter);
        }

        if ($cardId === self::CARD_SKIP || $advanceTurn) {
            $room['status'] = 'playing';
            $room['pending_trap_confirmations'] = [];
            $this->advanceTurn($room);
        }

        RoomRedisRepository::saveRoom($code, $room);

        if ($effectPayload) {
            broadcast(new CardEffectUsed($code, $effectPayload));
        }

        if ($forceDiceRollEvent !== null) {
            broadcast(new DiceRolled($code, $playerId, $forceDiceRollEvent, $room['players'][$playerId]['score']));
        }

        if (!$this->checkAndTriggerGameOver($room, $code)) {
            $this->broadcastState($code);
        }
        
        return response()->json([
            'success' => true,
            'myInventory' => $this->normalizeInventory($room['players'][$playerId]['inventory']),
            'state' => $this->buildRoomState($room, false),
        ]);
    }

    public function leaveRoom(Request $request, $code)
    {
        $playerId = session('player_id');
        if (!$playerId) return response()->json(['success' => false]);

        $room = RoomRedisRepository::getRoom($code);
        if (!$room) return response()->json(['success' => false]);

        if (isset($room['players'][$playerId])) {
            $isHost = $room['players'][$playerId]['is_host'];
            
            if ($isHost) {
                broadcast(new RoomClosed($code));
                RoomRedisRepository::deleteRoom($code);
            } else {
                unset($room['players'][$playerId]);
                RoomRedisRepository::saveRoom($code, $room);
                broadcast(new PlayerLeft($code, $playerId));
            }
        }

        session()->forget('player_id');
        return response()->json(['success' => true]);
    }
}
