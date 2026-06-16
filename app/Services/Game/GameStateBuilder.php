<?php

namespace App\Services\Game;

use App\Events\RoomStateUpdated;
use App\Events\RoomsUpdated;
use App\Repositories\RoomRedisRepository;
use App\Services\GameModes\ClassicMode;
use App\Services\GameModes\SurvivalMode;
use App\Services\GameModes\GameModeInterface;

class GameStateBuilder
{
    public function getModeService(string $mode): GameModeInterface
    {
        return $mode === 'survival' ? new SurvivalMode() : new ClassicMode();
    }

    public function normalizeInventory(?array $inventory): array
    {
        return array_values(array_filter($inventory ?? [], function ($cardId) {
            return is_string($cardId);
        }));
    }

    public function buildRoomState(array $room, bool $includeInventories = false): array
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
            'serverTime' => time(),
            'players' => $mappedPlayers,
        ];
    }

    public function broadcastState(string $code): void
    {
        $room = RoomRedisRepository::getRoom($code);
        if ($room) {
            broadcast(new RoomStateUpdated($code, $this->buildRoomState($room, false)));
        }
    }

    public function broadcastRoomsUpdated(): void
    {
        broadcast(new RoomsUpdated());
    }
}
