<?php

namespace App\Services\Cards\Spells;

use App\Services\Cards\CardEffectInterface;

class AngelCureEffect implements CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array
    {
        $player = &$room['players'][$playerId];
        $buffs = $player['active_buffs'] ?? [];
        
        $trapsToRemove = [
            'curse_heavy_bones',
            'forced_reroll',
            'reverse_fortune',
            'sabotaged',
            'blindfold',
        ];

        $removedTraps = [];
        $newBuffs = [];
        
        foreach ($buffs as $buff) {
            $isTrap = false;
            if (in_array($buff, $trapsToRemove)) {
                $isTrap = true;
            } elseif (str_starts_with($buff, 'time_bomb:')) {
                $isTrap = true;
            }

            if ($isTrap) {
                $removedTraps[] = $buff;
            } else {
                $newBuffs[] = $buff;
            }
        }

        if (empty($removedTraps)) {
            return [
                'success' => true,
                'payload' => [
                    'note' => $player['name'] . ' menggunakan Angel Cure, tapi tidak ada trap aktif yang ditemukan.',
                ]
            ];
        }

        $player['active_buffs'] = array_values($newBuffs);
        
        // Special cleanup for stateful traps
        foreach ($removedTraps as $trap) {
            if ($trap === 'forced_reroll') {
                unset($player['pending_forced_roll']);
                // Only unset require_extra_roll if it's not being used by a spell like 'rewind'
                $hasRewind = in_array('rewind', $newBuffs);
                if (!$hasRewind && !isset($player['pending_rewind_roll'])) {
                    unset($player['require_extra_roll']);
                }
            }
        }

        return [
            'success' => true,
            'payload' => [
                'note' => $player['name'] . ' menggunakan Angel Cure! Semua trap aktif (' . count($removedTraps) . ') berhasil dibatalkan.',
            ]
        ];
    }
}
