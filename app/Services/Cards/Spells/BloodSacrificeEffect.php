<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class BloodSacrificeEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        if ($room['players'][$playerId]['score'] <= 300) {
            return ['error' => 'LP Anda tidak cukup untuk mengorbankan darah! Minimal butuh lebih dari 300 LP.'];
        }

        $catalog = array_merge(config('cards.spells', []), config('cards.traps', []));

        $candidates = [];
        foreach ($room['players'] as $otherId => $player) {
            if ($otherId === $playerId) {
                continue;
            }
            $inventory = $player['inventory'] ?? [];
            foreach ($inventory as $idx => $cardId) {
                if (!isset($catalog[$cardId])) {
                    continue;
                }
                $type = $catalog[$cardId]['type'] ?? null;
                if ($type !== 'spell' && $type !== 'trap') {
                    continue;
                }
                $candidates[] = ['playerId' => $otherId, 'index' => $idx, 'cardId' => $cardId];
            }
        }

        if ($candidates === []) {
            return ['error' => 'Tidak ada lawan dengan kartu Spell atau Trap yang belum dipakai.'];
        }

        // Random opponent among those holding ≥1 eligible card, then random eligible card from them
        $byPlayer = [];
        foreach ($candidates as $c) {
            $byPlayer[$c['playerId']][] = $c;
        }
        $victimIds = array_keys($byPlayer);
        $victimId = $victimIds[array_rand($victimIds)];
        $pool = $byPlayer[$victimId];
        $pick = $pool[array_rand($pool)];

        $room['players'][$playerId]['score'] -= 300;

        $victimInv = $room['players'][$victimId]['inventory'];
        unset($victimInv[$pick['index']]);
        $room['players'][$victimId]['inventory'] = array_values($victimInv);
        $room['players'][$playerId]['inventory'][] = $pick['cardId'];

        $cardName = $catalog[$pick['cardId']]['name'] ?? $pick['cardId'];

        return [
            'success' => true,
            'payload' => [
                'targetPlayerId' => $victimId,
                'targetPlayerName' => $room['players'][$victimId]['name'],
                'stolenCardId' => $pick['cardId'],
                'stolenCardName' => $cardName,
                'note' => $room['players'][$playerId]['name']
                    . ' mengorbankan 300 LP dan mencuri '
                    . $cardName
                    . ' dari '
                    . $room['players'][$victimId]['name']
                    . '!',
            ],
        ];
    }
}
