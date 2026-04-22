<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class AmnestyEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        if ($room['players'][$playerId]['score'] < 1000) {
            $room['players'][$playerId]['active_buffs'][] = 'amnesty';
            return [
                'success' => true,
                'payload' => [
                    'note' => $room['players'][$playerId]['name'] . ' memohon Amnesty! Karena LP < 1000, dia akan menerima 0 damage giliran ini.',
                ]
            ];
        } else {
            return ['error' => 'LP harus di bawah 1000 untuk menggunakan Amnesty.'];
        }
    
    }
}
