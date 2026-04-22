<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class BloodSacrificeEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            return ['error' => 'Pilih korban yang valid.'];
        }

        if ($room['players'][$playerId]['score'] <= 300) {
            return ['error' => 'LP Anda tidak cukup untuk mengorbankan darah! Minimal butuh 300 LP.'];
        }

        $targetInventory = $room['players'][$targetId]['inventory'] ?? [];
        if (empty($targetInventory)) {
            return ['error' => 'Korban tidak punya kartu untuk dicuri!'];
        }

        // Kuras 300 LP
        $room['players'][$playerId]['score'] -= 300;

        // Curi kartu acak
        $randomIndex = array_rand($targetInventory);
        $stolenCard = $targetInventory[$randomIndex];

        // Pindahkan
        unset($targetInventory[$randomIndex]);
        $room['players'][$targetId]['inventory'] = array_values($targetInventory);
        $room['players'][$playerId]['inventory'][] = $stolenCard;

        return [
            'success' => true,
            'payload' => [
                'targetPlayerId' => $targetId,
                'targetPlayerName' => $room['players'][$targetId]['name'],
                'note' => $room['players'][$playerId]['name'] . ' mengorbankan 300 LP untuk mencuri kartu milik ' . $room['players'][$targetId]['name'] . '!',
            ]
        ];
    }
}
