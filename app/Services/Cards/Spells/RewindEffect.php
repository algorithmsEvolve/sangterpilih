<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class RewindEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        // Immediately roll 2 dice
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        $resultDice = min($dice1, $dice2);
        
        $modeService = new \App\Services\GameModes\SurvivalMode();
        $finalDiceResult = $modeService->processDiceRoll($room, $playerId, $resultDice);
        
        $room['players'][$playerId]['has_rolled_this_turn'] = true;
        $room['last_dice_result'] = $finalDiceResult;
        $room['last_roller_name'] = $room['players'][$playerId]['name'];

        return [
            'success' => true,
            'force_dice_roll_event' => $finalDiceResult,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . " memakai Rewind! Dadu 1: $dice1, Dadu 2: $dice2. Hasil terkecil yang diambil: $finalDiceResult.",
            ]
        ];
    
    }
}
