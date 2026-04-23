<?php

namespace App\Services\GameModes;

class ClassicMode implements GameModeInterface
{
    public function getInitialScore(int $totalPlayers): int
    {
        return 0;
    }

    public function processDiceRoll(array &$room, string $playerId, int $diceResult): array
    {
        $room['players'][$playerId]['score'] += $diceResult;
        return [$diceResult];
    }

    public function checkGameOverCondition(array $room): bool
    {
        // Classic mode ends when rounds exceed total_rounds
        return $room['current_round'] > $room['total_rounds'];
    }
}
