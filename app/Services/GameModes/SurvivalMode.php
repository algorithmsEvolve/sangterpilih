<?php

namespace App\Services\GameModes;

class SurvivalMode implements GameModeInterface
{
    public function getInitialScore(int $totalPlayers): int
    {
        return $totalPlayers < 4 ? 2000 : 3000;
    }

    public function processDiceRoll(array &$room, string $playerId, int $diceResult): void
    {
        $damage = $diceResult * 100;
        $room['players'][$playerId]['score'] -= $damage;
    }

    public function checkGameOverCondition(array $room): bool
    {
        // Survival mode ends if ANY player's LP drops to 0 or below
        foreach ($room['players'] as $player) {
            if ($player['score'] <= 0) {
                return true;
            }
        }
        return false;
    }
}
