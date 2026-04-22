<?php

namespace App\Services\Cards\Traps;

use App\Services\Cards\CardEffectInterface;

class ForcedRerollEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $room['players'][$targetId]['active_buffs'][] = 'forced_reroll';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memasang Forced Reroll pada ' . $room['players'][$targetId]['name'] . '! Harus lempar 2 dadu.',
            ]
        ];
    
    }
}
