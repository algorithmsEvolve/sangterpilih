<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class ShieldOfGraceEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'shield_of_grace';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memakai Shield of Grace! Damage akan dikurangi 50%.',
            ]
        ];
    
    }
}
