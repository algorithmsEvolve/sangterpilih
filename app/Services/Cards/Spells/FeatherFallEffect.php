<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class FeatherFallEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'feather_fall';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' mengaktifkan Feather Fall! Damage maksimal giliran ini hanya 100.',
            ]
        ];
    
    }
}
