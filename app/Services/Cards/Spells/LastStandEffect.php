<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class LastStandEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'last_stand';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menggunakan Last Stand! Dia tidak akan bisa mati di giliran ini.',
            ]
        ];
    
    }
}
