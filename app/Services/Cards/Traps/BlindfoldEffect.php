<?php

namespace App\Services\Cards\Traps;

use App\Services\Cards\CardEffectInterface;

class BlindfoldEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $room['players'][$targetId]['active_buffs'][] = 'blindfold';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' membutakan ' . $room['players'][$targetId]['name'] . '! Dadu berikutnya otomatis +1.',
            ]
        ];
    
    }
}
