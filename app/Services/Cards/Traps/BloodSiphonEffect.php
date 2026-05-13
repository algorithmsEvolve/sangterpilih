<?php

namespace App\Services\Cards\Traps;

use App\Services\Cards\CardEffectInterface;

class BloodSiphonEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $stealAmount = 300;
        
        $room['players'][$targetId]['score'] -= $stealAmount;
        $room['players'][$playerId]['score'] += $stealAmount;
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menghisap ' . $stealAmount . ' LP dari ' . $room['players'][$targetId]['name'] . '!',
            ]
        ];
    
    }
}
