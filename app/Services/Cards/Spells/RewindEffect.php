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
                'note' => $room['players'][$playerId]['name'] . " memakai Rewind! Harus nge-roll dadu 2 kali, dan otomatis hasil terkecil yang diambil.",
            ]
        ];
    
    }
}
