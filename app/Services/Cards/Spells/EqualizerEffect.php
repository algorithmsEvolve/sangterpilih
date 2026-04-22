<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class EqualizerEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $lowestScore = PHP_INT_MAX;
        foreach ($room['players'] as $p) {
            if ($p['score'] < $lowestScore) {
                $lowestScore = $p['score'];
            }
        }
        $room['players'][$playerId]['score'] = $lowestScore + 300;
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memakai Equalizer! LP nya kini menjadi ' . ($lowestScore + 300) . '.',
            ]
        ];
    
    }
}
