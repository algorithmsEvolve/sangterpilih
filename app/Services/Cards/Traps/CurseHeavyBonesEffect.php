<?php

namespace App\Services\Cards\Traps;

use App\Services\Cards\CardEffectInterface;

class CurseHeavyBonesEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        // Traps normally require a target
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            // For now if no target selected, select random other player
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $room['players'][$targetId]['active_buffs'][] = 'curse_heavy_bones';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' mengutuk ' . $room['players'][$targetId]['name'] . ' dengan Curse of Heavy Bones! (Damage 2x)',
            ]
        ];
    
    }
}
