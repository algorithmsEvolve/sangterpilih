<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class BpjsEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $playerName = $room['players'][$playerId]['name'];

        // Heal itself 700 LP
        $room['players'][$playerId]['score'] += 700;

        // Heal other players 300 LP
        foreach ($room['players'] as $id => $player) {
            if ($id !== $playerId) {
                $room['players'][$id]['score'] += 300;
            }
        }

        return [
            'success' => true,
            'payload' => [
                'note' => $playerName . ' menggunakan BPJS, memulihkan 700 LP untuk dirinya sendiri dan 300 LP untuk pemain lain!',
            ]
        ];
    }
}
