<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class TimeSkipEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'time_skip';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' melakukan Time Skip! Giliran ini tidak menerima damage.',
            ]
        ];
    
    }
}
