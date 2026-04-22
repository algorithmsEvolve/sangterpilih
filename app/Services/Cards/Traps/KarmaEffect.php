<?php

namespace App\Services\Cards\Traps;

use App\Services\Cards\CardEffectInterface;

class KarmaEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $room['players'][$playerId]['active_buffs'][] = 'karma:' . $targetId;
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' bersiap dengan Karma untuk ' . $room['players'][$targetId]['name'] . '!',
            ]
        ];
    
    }
}
