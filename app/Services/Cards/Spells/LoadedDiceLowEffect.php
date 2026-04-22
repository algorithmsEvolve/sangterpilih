<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class LoadedDiceLowEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'loaded_dice_low';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memakai Loaded Dice (Low)! Lemparan dadunya nanti pasti 1 atau 2.',
            ]
        ];
    
    }
}
