<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class ReflectStanceEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'reflect_stance';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' bersiap dengan Reflect Stance! Lemparan 5/6 akan dipantulkan.',
            ]
        ];
    
    }
}
