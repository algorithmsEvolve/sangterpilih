<?php

namespace App\Http\Controllers;

use App\Events\CardEffectUsed;
use App\Events\DiceRolled;
use App\Events\PlayerJoined;
use App\Events\PlayerLeft;
use App\Events\RoomClosed;
use App\Repositories\RoomRedisRepository;
use App\Services\Game\CardManager;
use App\Services\Game\GameStateBuilder;
use App\Services\Game\TurnManager;
use Illuminate\Http\Request;

class GameRedisController extends Controller
{
    private const LOADOUT_SELECTION_SECONDS = 120;

    private GameStateBuilder $stateBuilder;
    private TurnManager $turnManager;
    private CardManager $cardManager;

    public function __construct(
        GameStateBuilder $stateBuilder,
        TurnManager $turnManager,
        CardManager $cardManager
    ) {
        $this->stateBuilder = $stateBuilder;
        $this->turnManager = $turnManager;
        $this->cardManager = $cardManager;
    }

    public function roomsView()
    {
        return view('rooms', [
            'rooms' => RoomRedisRepository::listAvailableRooms(),
        ]);
    }

    public function roomsList()
    {
        return response()->json([
            'rooms' => RoomRedisRepository::listAvailableRooms(),
        ]);
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
        $this->stateBuilder->broadcastRoomsUpdated();
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
        $this->stateBuilder->broadcastRoomsUpdated();

        session(['player_id' => $playerId]);

        broadcast(new PlayerJoined($room['code'], $newPlayer));
        $this->stateBuilder->broadcastState($request->code);

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

        if (
            ($room['status'] ?? null) === 'selecting_cards'
            && !empty($room['selection_end_time'])
            && time() >= (int) $room['selection_end_time']
        ) {
            $this->turnManager->startPlayingFromLoadout($room, $code);
        }

        $currentPlayer = json_decode(json_encode($room['players'][$currentPlayerId]));

        $cardCatalog = array_values($this->cardManager->cardCatalog());
        
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

        $myInventory = $this->stateBuilder->normalizeInventory($room['players'][$currentPlayerId]['inventory'] ?? []);

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

        $modeService = $this->stateBuilder->getModeService($room['mode'] ?? 'classic');
        $initialScore = $modeService->getInitialScore(count($players));

        foreach ($room['players'] as $pId => $p) {
            $room['players'][$pId]['score'] = $initialScore;
        }

        if ($room['mode'] === 'survival') {
            $room['status'] = 'selecting_cards';
            $room['selection_end_time'] = time() + self::LOADOUT_SELECTION_SECONDS;
            foreach ($room['players'] as $pId => $p) {
                $room['players'][$pId]['has_selected_cards'] = false;
            }
            RoomRedisRepository::saveRoom($code, $room);
            $this->stateBuilder->broadcastState($room['code']);
            $this->stateBuilder->broadcastRoomsUpdated();
            return response()->json([
                'success' => true,
                'state' => $this->stateBuilder->buildRoomState($room, false),
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

        $this->turnManager->startTurnSnapshot($room, $firstPlayer);
        RoomRedisRepository::saveRoom($code, $room);

        broadcast(new GameStarted($room['code'], $room['current_turn_player_id']));
        $this->stateBuilder->broadcastState($room['code']);
        $this->stateBuilder->broadcastRoomsUpdated();

        return response()->json([
            'success' => true,
            'state' => $this->stateBuilder->buildRoomState($room, false),
            'myInventory' => $this->stateBuilder->normalizeInventory($currentPlayer['inventory']),
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
        $catalog = $this->cardManager->cardCatalog();

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

        $isExpired = !empty($room['selection_end_time']) && time() >= (int) $room['selection_end_time'];

        // Check if all players are ready
        $allReady = true;
        if (!$isExpired) {
            foreach ($room['players'] as $p) {
                if (empty($p['has_selected_cards'])) {
                    $allReady = false;
                    break;
                }
            }
        }

        // If everyone is ready, transition to playing
        $startedPlaying = false;
        if ($allReady || $isExpired) {
            $this->turnManager->startPlayingFromLoadout($room, $code);
            $startedPlaying = true;
        } else {
            RoomRedisRepository::saveRoom($code, $room);
        }

        if (!$startedPlaying) {
            $this->stateBuilder->broadcastState($room['code']);
        }

        return response()->json([
            'success' => true,
            'myInventory' => $this->stateBuilder->normalizeInventory($room['players'][$playerId]['inventory']),
            'state' => $this->stateBuilder->buildRoomState($room, false),
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
        
        $modeService = $this->stateBuilder->getModeService($room['mode'] ?? 'classic');
        $finalDiceResult = $modeService->processDiceRoll($room, $playerId, $diceResult);

        if (!empty($room['players'][$playerId]['require_extra_roll'])) {
            $room['players'][$playerId]['has_rolled_this_turn'] = false;
        } else {
            $room['players'][$playerId]['has_rolled_this_turn'] = true;
        }
        
        $room['last_dice_result'] = $finalDiceResult;
        $room['last_roller_name'] = $room['players'][$playerId]['name'];

        RoomRedisRepository::saveRoom($code, $room);
        broadcast(new DiceRolled($room['code'], $playerId, $finalDiceResult, $room['players'][$playerId]['score']));
        
        if ($this->turnManager->checkAndTriggerGameOver($room, $code)) {
            return response()->json([
                'success' => true,
                'diceResult' => $finalDiceResult,
                'score' => $room['players'][$playerId]['score'],
                'state' => $this->stateBuilder->buildRoomState($room, false),
                'myInventory' => $this->stateBuilder->normalizeInventory($room['players'][$playerId]['inventory']),
            ]);
        }

        $this->stateBuilder->broadcastState($room['code']);

        return response()->json([
            'success' => true,
            'diceResult' => $finalDiceResult,
            'score' => $room['players'][$playerId]['score'],
            'state' => $this->stateBuilder->buildRoomState($room, false),
            'myInventory' => $this->stateBuilder->normalizeInventory($room['players'][$playerId]['inventory']),
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

        $this->turnManager->advanceTurn($room);
        RoomRedisRepository::saveRoom($code, $room);
        $this->stateBuilder->broadcastState($code);

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

        $catalog = $this->cardManager->cardCatalog();
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
        
        if (!$this->turnManager->checkAndTriggerGameOver($room, $code)) {
            $this->stateBuilder->broadcastState($code);
        }

        return response()->json([
            'success' => true,
            'state' => $this->stateBuilder->buildRoomState($room, false),
            'myInventory' => $this->stateBuilder->normalizeInventory($room['players'][$playerId]['inventory']),
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

        $catalog = $this->cardManager->cardCatalog();
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

        $result = $this->cardManager->applyCardEffect($room, $playerId, $cardId, $request->all(), $catalog);
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        $effectPayload = $result['effectPayload'];
        $advanceTurn = $result['advanceTurn'];
        $forceDiceRollEvent = $result['forceDiceRollEvent'];

        $this->cardManager->removeCardFromInventory($room, $playerId, $cardId);

        if ($cardId === CardManager::CARD_SKIP || $advanceTurn) {
            $room['status'] = 'playing';
            $room['pending_trap_confirmations'] = [];
            $this->turnManager->advanceTurn($room);
        }

        RoomRedisRepository::saveRoom($code, $room);

        if ($effectPayload) {
            broadcast(new CardEffectUsed($code, $effectPayload));
        }

        if ($forceDiceRollEvent !== null) {
            broadcast(new DiceRolled($code, $playerId, $forceDiceRollEvent, $room['players'][$playerId]['score']));
        }

        if (!$this->turnManager->checkAndTriggerGameOver($room, $code)) {
            $this->stateBuilder->broadcastState($code);
        }

        return response()->json([
            'success' => true,
            'myInventory' => $this->stateBuilder->normalizeInventory($room['players'][$playerId]['inventory']),
            'state' => $this->stateBuilder->buildRoomState($room, false),
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
                $this->stateBuilder->broadcastRoomsUpdated();
            } else {
                unset($room['players'][$playerId]);
                RoomRedisRepository::saveRoom($code, $room);
                broadcast(new PlayerLeft($code, $playerId));
                $this->stateBuilder->broadcastRoomsUpdated();
            }
        }

        session()->forget('player_id');
        return response()->json(['success' => true]);
    }
}
