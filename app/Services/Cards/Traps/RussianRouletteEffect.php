<?php

namespace App\Services\Cards\Traps;

use App\Services\Cards\CardEffectInterface;

class RussianRouletteEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        foreach ($room['players'] as $id => $p) {
            $room['players'][$id]['score'] -= 300;
        }
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menarik pelatuk Russian Roulette! SEMUA pemain kena 300 damage.',
            ]
        ];
    
    }
}
