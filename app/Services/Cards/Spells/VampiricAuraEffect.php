<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class VampiricAuraEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $room['players'][$playerId]['active_buffs'][] = 'vampiric_aura';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menyerap energi dengan Vampiric Aura! Lemparan dadu akan menyembuhkan.',
            ]
        ];
    
    }
}
