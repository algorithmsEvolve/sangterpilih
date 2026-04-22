<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class RewindEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'rewind';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memakai Rewind! Dadu akan dilempar dua kali dan diambil yang terkecil.',
            ]
        ];
    
    }
}
