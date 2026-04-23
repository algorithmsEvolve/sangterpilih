<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class TimeSkipEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['has_rolled_this_turn'] = true;
        return [
            'success' => true,
            'advance_turn' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menggunakan Time Skip! Langsung akhiri giliran tanpa damage.',
            ]
        ];
    
    }
}
