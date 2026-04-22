<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class GamblersShieldEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $guess = $data['guess'] ?? null;
        if (!in_array($guess, ['odd', 'even'])) {
            return ['error' => 'Harus tebak ganjil (odd) atau genap (even).'];
        }

        $buffName = $guess === 'odd' ? 'gamblers_shield_odd' : 'gamblers_shield_even';
        $room['players'][$playerId]['active_buffs'][] = $buffName;

        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memakai Gamblers Shield dan menebak ' . ($guess === 'odd' ? 'Ganjil' : 'Genap') . '!',
            ]
        ];
    }
}
