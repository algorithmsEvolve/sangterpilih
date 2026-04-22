<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class AdrenalineRushEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'adrenaline_rush';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menyuntikkan Adrenaline Rush! Damage yang diterima akan disembuhkan 2x lipat giliran depan.',
            ]
        ];
    
    }
}
