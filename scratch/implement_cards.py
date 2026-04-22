import os

spells_logic = {
    'FeatherFallEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'feather_fall';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' mengaktifkan Feather Fall! Damage maksimal giliran ini hanya 100.',
            ]
        ];
    """,
    'ShieldOfGraceEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'shield_of_grace';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memakai Shield of Grace! Damage akan dikurangi 50%.',
            ]
        ];
    """,
    'LoadedDiceLowEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'loaded_dice_low';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memakai Loaded Dice (Low)! Lemparan dadunya nanti pasti 1 atau 2.',
            ]
        ];
    """,
    'VampiricAuraEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'vampiric_aura';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menyerap energi dengan Vampiric Aura! Lemparan dadu akan menyembuhkan.',
            ]
        ];
    """,
    'TimeSkipEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'time_skip';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' melakukan Time Skip! Giliran ini tidak menerima damage.',
            ]
        ];
    """,
    'RewindEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'rewind';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memakai Rewind! Dadu akan dilempar dua kali dan diambil yang terkecil.',
            ]
        ];
    """,
    'AmnestyEffect': """
        if ($room['players'][$playerId]['score'] < 1000) {
            $room['players'][$playerId]['active_buffs'][] = 'amnesty';
            return [
                'success' => true,
                'payload' => [
                    'note' => $room['players'][$playerId]['name'] . ' memohon Amnesty! Karena LP < 1000, dia akan menerima 0 damage giliran ini.',
                ]
            ];
        } else {
            return ['error' => 'LP harus di bawah 1000 untuk menggunakan Amnesty.'];
        }
    """,
    'ReflectStanceEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'reflect_stance';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' bersiap dengan Reflect Stance! Lemparan 5/6 akan dipantulkan.',
            ]
        ];
    """,
    'GuardianAngelEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'guardian_angel';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' dilindungi Guardian Angel! Kebal dari trap musuh.',
            ]
        ];
    """,
    'EqualizerEffect': """
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
    """,
    'LastStandEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'last_stand';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menggunakan Last Stand! Dia tidak akan bisa mati di giliran ini.',
            ]
        ];
    """,
    'AdrenalineRushEffect': """
        $room['players'][$playerId]['active_buffs'][] = 'adrenaline_rush';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menyuntikkan Adrenaline Rush! Damage yang diterima akan disembuhkan 2x lipat giliran depan.',
            ]
        ];
    """,
}

traps_logic = {
    'CurseHeavyBonesEffect': """
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
    """,
    'BloodSiphonEffect': """
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $stealAmount = min(300, $room['players'][$targetId]['score'] - 1);
        if ($stealAmount <= 0) return ['error' => 'Target sudah sekarat!'];
        
        $room['players'][$targetId]['score'] -= $stealAmount;
        $room['players'][$playerId]['score'] += $stealAmount;
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menghisap ' . $stealAmount . ' LP dari ' . $room['players'][$targetId]['name'] . '!',
            ]
        ];
    """,
    'ForcedRerollEffect': """
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
    """,
    'PoisonDartEffect': """
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $room['players'][$targetId]['score'] -= 200;
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' melempar Poison Dart! ' . $room['players'][$targetId]['name'] . ' terkena 200 damage instan.',
            ]
        ];
    """,
    'KarmaEffect': """
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
    """,
    'ReverseFortuneEffect': """
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $room['players'][$targetId]['active_buffs'][] = 'reverse_fortune';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' memasang Reverse Fortune pada ' . $room['players'][$targetId]['name'] . '!',
            ]
        ];
    """,
    'SabotageEffect': """
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $room['players'][$targetId]['active_buffs'][] = 'sabotaged';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' mensabotase ' . $room['players'][$targetId]['name'] . '! Tidak bisa pakai spell.',
            ]
        ];
    """,
    'RussianRouletteEffect': """
        foreach ($room['players'] as $id => $p) {
            $room['players'][$id]['score'] -= 300;
        }
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menarik pelatuk Russian Roulette! SEMUA pemain kena 300 damage.',
            ]
        ];
    """,
    'TimeBombEffect': """
        $targetId = $data['target_player_id'] ?? null;
        if (!$targetId || !isset($room['players'][$targetId])) {
            $otherIds = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (empty($otherIds)) return ['error' => 'Tidak ada target lain.'];
            $targetId = $otherIds[array_rand($otherIds)];
        }
        $room['players'][$targetId]['active_buffs'][] = 'time_bomb:2';
        return [
            'success' => true,
            'payload' => [
                'note' => $room['players'][$playerId]['name'] . ' menanam Time Bomb pada ' . $room['players'][$targetId]['name'] . '! Meledak 2 putaran lagi.',
            ]
        ];
    """,
    'BlindfoldEffect': """
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
    """
}

base_dir = os.path.join(os.path.dirname(__file__), '../app/Services/Cards')

def update_file(path, logic):
    with open(path, 'r') as f:
        content = f.read()
    
    # Simple regex or string replacement to inject logic
    # Find the function apply body
    start_str = "public function apply(array &$room, string $playerId, array $data): ?array\n    {"
    start_idx = content.find(start_str)
    if start_idx == -1:
        return
    start_idx += len(start_str)
    end_idx = content.find("}\n}", start_idx)
    
    new_content = content[:start_idx] + logic + "\n    " + content[end_idx:]
    with open(path, 'w') as f:
        f.write(new_content)

for spell, logic in spells_logic.items():
    update_file(os.path.join(base_dir, 'Spells', f"{spell}.php"), logic)

for trap, logic in traps_logic.items():
    update_file(os.path.join(base_dir, 'Traps', f"{trap}.php"), logic)

print("Updated implementations.")
