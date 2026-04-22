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
use App\Models\Player;
use App\Models\Room;
use Illuminate\Http\Request;

class GameController extends Controller
{
    private const CARD_SKIP = 'skip_si';
    private const CARD_MULTIPLIER = 'multiplier';

    private function cardCatalog(): array
    {
        return [];
    }

    private function normalizeInventory(?array $inventory): array
    {
        return array_values(array_filter($inventory ?? [], function ($cardId) {
            return is_string($cardId);
        }));
    }

    private function buildRoomState(Room $room, bool $includeInventories = false): array
    {
        $room->load('players');
        $players = $room->players->sortBy('id')->values()->map(function (Player $p) use ($includeInventories) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'score' => $p->score,
                'is_host' => (bool) $p->is_host,
                'hasRolledThisTurn' => (bool) $p->has_rolled_this_turn,
                'inventory' => $includeInventories ? $this->normalizeInventory($p->inventory) : [],
            ];
        })->toArray();

        return [
            'status' => $room->status,
            'currentTurn' => $room->current_turn_player_id,
            'currentRound' => (int) $room->current_round,
            'totalRounds' => (int) $room->total_rounds,
            'turnHasSkip' => (bool) $room->turn_has_skip,
            'turnMultiplierPlayerId' => $room->turn_multiplier_player_id,
            'lastDiceResult' => $room->last_dice_result,
            'lastRollerName' => $room->last_roller_name,
            'pendingTrapConfirmations' => $room->pending_trap_confirmations ?? [],
            'trapTargetPlayerId' => $room->trap_target_player_id,
            'players' => $players,
        ];
    }

    private function broadcastState(Room $room): void
    {
        $room->refresh();
        broadcast(new RoomStateUpdated($room->code, $this->buildRoomState($room, false)));
    }

    private function startTurnSnapshot(Room $room, Player $activePlayer): void
    {
        $room->active_turn_snapshot = [
            'player_id' => $activePlayer->id,
            'start_score' => (int) $activePlayer->score,
            'rolled' => false,
        ];
        $room->turn_has_skip = false;
        $room->turn_multiplier_player_id = null;
        $room->pending_trap_confirmations = null;
        $room->trap_target_player_id = null;
        $room->save();
    }

    private function advanceTurn(Room $room): ?array
    {
        $players = $room->players()->orderBy('id')->get();
        if ($players->count() < 2) {
            $room->status = 'finished';
            $room->save();
            return null;
        }

        $currentPlayer = $players->firstWhere('id', $room->current_turn_player_id);
        if ($currentPlayer) {
            $currentPlayer->has_rolled_this_turn = false;
            $currentPlayer->save();
        }

        $nextTurnIndex = ((int) $room->turn_index) + 1;
        $isNextRound = $nextTurnIndex >= $players->count();

        if ($isNextRound) {
            $nextTurnIndex = 0;
            $room->current_round = ((int) $room->current_round) + 1;
        }

        if (((int) $room->current_round) > ((int) $room->total_rounds)) {
            $room->status = 'finished';
            $room->save();
            $leaderboard = $room->players()->orderByDesc('score')->get()->toArray();
            broadcast(new GameOver($room->code, $leaderboard));
            $this->broadcastState($room);
            return null;
        }

        $nextPlayer = $players[$nextTurnIndex];
        $room->turn_index = $nextTurnIndex;
        $room->current_turn_player_id = $nextPlayer->id;
        $room->last_dice_result = null;
        $room->last_roller_name = null;
        $room->save();

        $this->startTurnSnapshot($room, $nextPlayer);
        return ['nextPlayerId' => $nextPlayer->id];
    }

    public function createRoom(Request $request)
    {
        $request->validate([
            'host_name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:rooms,code',
        ]);

        $room = Room::create([
            'code' => $request->code,
            'status' => 'waiting',
        ]);

        $player = $room->players()->create([
            'name' => $request->host_name,
            'is_host' => true,
            'inventory' => [],
        ]);

        session(['player_id' => $player->id]);

        return redirect('/room/' . $room->code);
    }

    public function joinRoom(Request $request)
    {
        $request->validate([
            'player_name' => 'required|string|max:255',
            'code' => 'required|string|exists:rooms,code',
        ]);

        $room = Room::where('code', $request->code)->firstOrFail();

        if ($room->status !== 'waiting') {
            return back()->with('error', 'Room sudah mulai bermain!');
        }

        $player = $room->players()->create([
            'name' => $request->player_name,
            'is_host' => false,
            'inventory' => [],
        ]);

        session(['player_id' => $player->id]);

        broadcast(new PlayerJoined($roomCode = $room->code, $player->toArray()));

        return redirect('/room/' . $room->code);
    }

    public function roomView($code)
    {
        $room = Room::where('code', $code)->with('players')->firstOrFail();
        $currentPlayerId = session('player_id');

        if (!$currentPlayerId) {
            return redirect('/')->with('error', 'Silakan join/create room terlebih dahulu.');
        }

        $currentPlayer = Player::findOrFail($currentPlayerId);
        $cardCatalog = array_values($this->cardCatalog());
        $playersPublic = $room->players->sortBy('id')->values()->map(function (Player $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'score' => $p->score,
                'is_host' => (bool) $p->is_host,
                'has_rolled_this_turn' => (bool) $p->has_rolled_this_turn,
            ];
        })->toArray();
        $myInventory = $this->normalizeInventory($currentPlayer->inventory);

        return view('room', compact('room', 'currentPlayer', 'cardCatalog', 'playersPublic', 'myInventory'));
    }

    public function startGame($code)
    {
        $room = Room::where('code', $code)->firstOrFail();
        $currentPlayerId = session('player_id');
        $currentPlayer = Player::find($currentPlayerId);

        if (!$currentPlayer || !$currentPlayer->is_host) {
            return response()->json(['error' => 'Cuma host yang bisa start game.'], 403);
        }

        $players = $room->players()->orderBy('id')->get();
        if ($players->count() < 2) {
            return response()->json(['error' => 'Butuh minimal 2 pemain!'], 400);
        }

        $firstPlayer = $players->first();
        $room->status = 'playing';
        $room->current_turn_player_id = $firstPlayer->id;
        $room->current_round = 1;
        $room->total_rounds = max(5, (int) $room->total_rounds);
        $room->turn_index = 0;
        $room->turn_has_skip = false;
        $room->turn_multiplier_player_id = null;
        $room->last_dice_result = null;
        $room->last_roller_name = null;
        $room->save();

        broadcast(new GameStarted($room->code, $room->current_turn_player_id));
        $this->startTurnSnapshot($room, $firstPlayer);
        $this->broadcastState($room);

        return response()->json([
            'success' => true,
            'state' => $this->buildRoomState($room->fresh(), false),
            'myInventory' => $this->normalizeInventory($currentPlayer->inventory),
        ]);
    }

    public function rollDice(Request $request, $code)
    {
        $room = Room::where('code', $code)->firstOrFail();
        $currentPlayerId = session('player_id');
        $player = Player::find($currentPlayerId);

        if (!$player) {
            return response()->json(['error' => 'Player tidak ditemukan.'], 404);
        }

        if ($room->status !== 'playing') {
            return response()->json(['error' => 'Game is not in playing state'], 400);
        }

        if ($room->current_turn_player_id !== $player->id) {
            return response()->json(['error' => 'Bukan giliranmu!'], 400);
        }

        if ($player->has_rolled_this_turn) {
            return response()->json(['error' => 'Kamu sudah melempar dadu!'], 400);
        }

        $diceResult = rand(1, 6);
        if ((int) $room->turn_multiplier_player_id === (int) $player->id) {
            $diceResult *= 2;
            $room->turn_multiplier_player_id = null;
        }

        $snapshot = $room->active_turn_snapshot ?? [];
        $snapshot['rolled'] = true;
        $room->active_turn_snapshot = $snapshot;

        $player->score += $diceResult;
        $player->has_rolled_this_turn = true;
        $player->save();

        $room->last_dice_result = $diceResult;
        $room->last_roller_name = $player->name;
        $room->save();

        broadcast(new DiceRolled($room->code, $player->id, $diceResult, $player->score));
        $this->broadcastState($room);

        return response()->json([
            'success' => true,
            'diceResult' => $diceResult,
            'score' => $player->score,
            'state' => $this->buildRoomState($room->fresh(), false),
            'myInventory' => $this->normalizeInventory($player->inventory),
        ]);
    }

    public function endTurn($code)
    {
        $room = Room::where('code', $code)->firstOrFail();
        $currentPlayerId = session('player_id');
        $player = Player::find($currentPlayerId);

        if (!$player || $room->current_turn_player_id !== $player->id) {
            return response()->json(['error' => 'Bukan giliranmu!'], 400);
        }

        if (!$player->has_rolled_this_turn) {
            return response()->json(['error' => 'Lempar dadu dulu, baru akhiri giliran.'], 400);
        }

        // Logic check trap
        $potentialTrapUsers = $room->players()
            ->where('id', '!=', $player->id)
            ->get()
            ->filter(function ($p) {
                $inv = $p->inventory ?? [];
                return in_array(self::CARD_SKIP, $inv);
            });

        if ($potentialTrapUsers->count() > 0) {
            $room->status = 'awaiting_trap_confirmation';
            $room->pending_trap_confirmations = $potentialTrapUsers->pluck('id')->toArray();
            $room->trap_target_player_id = $player->id;
            $room->save();
            $this->broadcastState($room->fresh());
            return response()->json(['success' => true]);
        }

        $this->advanceTurn($room);
        $this->broadcastState($room->fresh());

        return response()->json(['success' => true]);
    }

    public function skipTrap(Request $request, $code)
    {
        $room = Room::where('code', $code)->firstOrFail();
        $currentPlayerId = session('player_id');

        if ($room->status !== 'awaiting_trap_confirmation') {
            return response()->json(['error' => 'Gak ada trap yang perlu dikonfirmasi.'], 400);
        }

        $pending = $room->pending_trap_confirmations ?? [];
        if (!in_array($currentPlayerId, $pending)) {
            return response()->json(['error' => 'Lo gak perlu konfirmasi trap.'], 400);
        }

        $pending = array_values(array_filter($pending, function ($id) use ($currentPlayerId) {
            return $id != $currentPlayerId;
        }));
        $room->pending_trap_confirmations = $pending;

        if (empty($pending)) {
            $room->status = 'playing';
            $this->advanceTurn($room);
        }

        $room->save();
        $this->broadcastState($room->fresh());
        return response()->json(['success' => true]);
    }

    public function buyCard(Request $request, $code)
    {
        $request->validate([
            'card_id' => 'required|string',
        ]);

        $room = Room::where('code', $code)->firstOrFail();
        $player = Player::find(session('player_id'));
        $catalog = $this->cardCatalog();
        $cardId = $request->card_id;

        if (!$player) {
            return response()->json(['error' => 'Player tidak ditemukan.'], 404);
        }

        if ($room->status !== 'playing') {
            return response()->json(['error' => 'Shop cuma aktif saat game berjalan.'], 400);
        }

        if (!isset($catalog[$cardId])) {
            return response()->json(['error' => 'Kartu tidak valid.'], 400);
        }

        $card = $catalog[$cardId];
        if ($player->score < $card['price']) {
            return response()->json(['error' => 'Poin lo belum cukup buat beli kartu ini.'], 400);
        }

        $inventory = $this->normalizeInventory($player->inventory);
        $inventory[] = $cardId;
        $player->inventory = $inventory;
        $player->score -= $card['price'];
        $player->save();

        $this->broadcastState($room);
        return response()->json([
            'success' => true,
            'state' => $this->buildRoomState($room->fresh(), false),
            'myInventory' => $this->normalizeInventory($player->fresh()->inventory),
        ]);
    }

    public function useCard(Request $request, $code)
    {
        $request->validate([
            'card_id' => 'required|string',
        ]);

        $room = Room::where('code', $code)->firstOrFail();
        $player = Player::find(session('player_id'));
        $catalog = $this->cardCatalog();
        $cardId = $request->card_id;

        if (!$player) {
            return response()->json(['error' => 'Player tidak ditemukan.'], 404);
        }

        if ($room->status !== 'playing' && $room->status !== 'awaiting_trap_confirmation') {
            return response()->json(['error' => 'Kartu cuma bisa dipakai saat game berjalan.'], 400);
        }

        if (!isset($catalog[$cardId])) {
            return response()->json(['error' => 'Kartu tidak valid.'], 400);
        }

        $inventory = $this->normalizeInventory($player->inventory);
        $cardIndex = array_search($cardId, $inventory, true);
        if ($cardIndex === false) {
            return response()->json(['error' => 'Kartu ini gak ada di inventory lo.'], 400);
        }

        $effectPayload = null;

        if ($cardId === self::CARD_MULTIPLIER) {
            if ((int) $room->current_turn_player_id !== (int) $player->id) {
                return response()->json(['error' => 'Spell multiplier cuma bisa dipakai saat giliran lo.'], 400);
            }

            if ((int) $room->turn_multiplier_player_id === (int) $player->id) {
                return response()->json(['error' => 'Multiplier lo udah aktif di giliran ini.'], 400);
            }

            if ($player->has_rolled_this_turn) {
                $snapshot = $room->active_turn_snapshot ?? [];
                $startScore = (int) ($snapshot['start_score'] ?? $player->score);
                $turnGain = max(0, $player->score - $startScore);
                if ($turnGain <= 0) {
                    return response()->json(['error' => 'Belum ada hasil roll yang bisa digandain.'], 400);
                }
                $player->score = $startScore + ($turnGain * 2);
                $player->save();
                if ($room->last_dice_result) {
                    $room->last_dice_result = $room->last_dice_result * 2;
                }
                $room->turn_multiplier_player_id = null;
                $room->save();
                $effectPayload = [
                    'cardId' => $cardId,
                    'cardName' => $catalog[$cardId]['name'],
                    'cardType' => $catalog[$cardId]['type'],
                    'usedByPlayerId' => $player->id,
                    'usedByPlayerName' => $player->name,
                    'targetPlayerId' => $player->id,
                    'targetPlayerName' => $player->name,
                    'note' => $player->name . ' nge-boost hasil dadu jadi x2.',
                ];
            } else {
                $room->turn_multiplier_player_id = $player->id;
                $room->save();
                $effectPayload = [
                    'cardId' => $cardId,
                    'cardName' => $catalog[$cardId]['name'],
                    'cardType' => $catalog[$cardId]['type'],
                    'usedByPlayerId' => $player->id,
                    'usedByPlayerName' => $player->name,
                    'targetPlayerId' => $player->id,
                    'targetPlayerName' => $player->name,
                    'note' => $player->name . ' siapin multiplier. Roll berikutnya bakal x2.',
                ];
            }
        }

        if ($cardId === self::CARD_SKIP) {
            if ((int) $room->current_turn_player_id === (int) $player->id) {
                return response()->json(['error' => 'Trap skip dipakai buat ngerjain orang lain, bukan diri sendiri.'], 400);
            }

            if ($room->turn_has_skip) {
                return response()->json(['error' => 'Skip udah kepake di giliran ini, gak bisa dobel.'], 400);
            }

            $target = Player::find($room->current_turn_player_id);
            if ($target) {
                $snapshot = $room->active_turn_snapshot ?? [];
                if (($snapshot['player_id'] ?? null) === $target->id && isset($snapshot['start_score'])) {
                    $target->score = (int) $snapshot['start_score'];
                    $target->has_rolled_this_turn = false;
                    $target->save();
                    $room->last_dice_result = null;
                    $room->last_roller_name = null;
                }
            }

            $room->turn_has_skip = true;
            $room->save();
            $targetPlayerId = $target ? $target->id : null;
            $targetPlayerName = $target ? $target->name : null;
            $effectPayload = [
                'cardId' => $cardId,
                'cardName' => $catalog[$cardId]['name'],
                'cardType' => $catalog[$cardId]['type'],
                'usedByPlayerId' => $player->id,
                'usedByPlayerName' => $player->name,
                'targetPlayerId' => $targetPlayerId,
                'targetPlayerName' => $targetPlayerName,
                'note' => $player->name . ' ngeskip giliran ' . ($targetPlayerName ?? 'target') . '. Sadis!',
            ];
        }

        unset($inventory[$cardIndex]);
        $player->inventory = array_values($inventory);
        $player->save();

        if ($cardId === self::CARD_SKIP) {
            $room->status = 'playing';
            $room->pending_trap_confirmations = null;
            $room->save();
            $this->advanceTurn($room->fresh());
        }

        if ($effectPayload) {
            broadcast(new CardEffectUsed($room->code, $effectPayload));
        }

        $this->broadcastState($room->fresh());
        return response()->json([
            'success' => true,
            'myInventory' => $this->normalizeInventory($player->fresh()->inventory),
            'state' => $this->buildRoomState($room->fresh(), false),
        ]);
    }

    public function leaveRoom(Request $request, $code)
    {
        $currentPlayerId = session('player_id');
        if (!$currentPlayerId) {
            return response()->json(['success' => false]);
        }

        $player = Player::find($currentPlayerId);
        if ($player && $player->is_host) {
            $room = Room::where('code', $code)->first();
            if ($room) {
                broadcast(new RoomClosed($room->code));
                $room->delete();
            }
        } elseif ($player) {
            $playerId = $player->id;
            $player->delete();
            broadcast(new PlayerLeft($code, $playerId));
        }

        return response()->json(['success' => true]);
    }
}
