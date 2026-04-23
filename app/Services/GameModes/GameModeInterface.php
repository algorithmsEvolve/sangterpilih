<?php

namespace App\Services\GameModes;

interface GameModeInterface
{
    /**
     * Determines the starting score/Life Points for players.
     */
    public function getInitialScore(int $totalPlayers): int;

    /**
     * Processes how a dice roll affects the game state. Returns the final dice result (which might be modified by effects).
     */
    public function processDiceRoll(array &$room, string $playerId, int $diceResult): array;

    /**
     * Checks if the game has reached an ending condition.
     */
    public function checkGameOverCondition(array $room): bool;
}
