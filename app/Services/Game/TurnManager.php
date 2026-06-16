<?php

namespace App\Services\Game;

use App\Events\GameOver;
use App\Events\GameStarted;
use App\Repositories\RoomRedisRepository;

class TurnManager
{
    private GameStateBuilder $stateBuilder;

    public function __construct(GameStateBuilder $stateBuilder)
    {
        $this->stateBuilder = $stateBuilder;
    }

    public function startTurnSnapshot(array &$room, array $activePlayer): void
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

    public function advanceTurn(array &$room): ?array
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

    public function checkAndTriggerGameOver(array &$room, string $code): bool
    {
        if (($room['status'] ?? '') === 'finished') return true;

        $modeService = $this->stateBuilder->getModeService($room['mode'] ?? 'classic');
        if ($modeService->checkGameOverCondition($room)) {
            $room['status'] = 'finished';
            $leaderboard = array_values($room['players']);
            usort($leaderboard, function($a, $b) { return $b['score'] <=> $a['score']; });

            RoomRedisRepository::saveRoom($code, $room);
            broadcast(new GameOver($code, $leaderboard));
            $this->stateBuilder->broadcastState($code);
            return true;
        }
        return false;
    }

    public function startPlayingFromLoadout(array &$room, string $code): void
    {
        $players = array_values($room['players']);
        usort($players, function($a, $b) { return strcmp($a['id'], $b['id']); });

        $firstPlayer = $players[0];
        foreach ($room['players'] as $pId => $p) {
            $room['players'][$pId]['inventory'] = $p['inventory'] ?? [];
            $room['players'][$pId]['has_selected_cards'] = true;
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
        $room['selection_end_time'] = null;

        $this->startTurnSnapshot($room, $firstPlayer);
        RoomRedisRepository::saveRoom($code, $room);

        broadcast(new GameStarted($room['code'], $room['current_turn_player_id']));
        $this->stateBuilder->broadcastState($code);
        $this->stateBuilder->broadcastRoomsUpdated();
    }
}
