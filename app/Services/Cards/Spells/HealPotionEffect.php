<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class HealPotionEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['score'] += 500;

        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' meminum Heal Potion dan memulihkan 500 LP!',
            ]
        ];
    }
}
