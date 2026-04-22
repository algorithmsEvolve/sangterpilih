<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class GuardianAngelEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'guardian_angel';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' dilindungi Guardian Angel! Kebal dari trap musuh.',
            ]
        ];
    
    }
}
