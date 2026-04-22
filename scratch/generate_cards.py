import os

spells = [
    'HealPotionEffect',
    'FeatherFallEffect',
    'ShieldOfGraceEffect',
    'LoadedDiceLowEffect',
    'VampiricAuraEffect',
    'TimeSkipEffect',
    'GamblersShieldEffect',
    'RewindEffect',
    'BloodSacrificeEffect',
    'AmnestyEffect',
    'ReflectStanceEffect',
    'GuardianAngelEffect',
    'EqualizerEffect',
    'LastStandEffect',
    'AdrenalineRushEffect',
]

traps = [
    'CurseHeavyBonesEffect',
    'BloodSiphonEffect',
    'ForcedRerollEffect',
    'PoisonDartEffect',
    'KarmaEffect',
    'ReverseFortuneEffect',
    'SabotageEffect',
    'RussianRouletteEffect',
    'TimeBombEffect',
    'BlindfoldEffect',
]

base_dir = os.path.join(os.path.dirname(__file__), '../app/Services/Cards')
os.makedirs(os.path.join(base_dir, 'Spells'), exist_ok=True)
os.makedirs(os.path.join(base_dir, 'Traps'), exist_ok=True)

interface_code = """<?php

namespace App\Services\Cards;

interface CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array;
}
"""
with open(os.path.join(base_dir, 'CardEffectInterface.php'), 'w') as f:
    f.write(interface_code)

def get_template(namespace, class_name):
    return f"""<?php

namespace {namespace};

use App\Services\Cards\CardEffectInterface;

class {class_name} implements CardEffectInterface
{{
    public function apply(array &$room, string $playerId, array $data): ?array
    {{
        // TODO: Implement logic
        return [
            'success' => true,
            'payload' => [
                'cardId' => 'unknown',
                'cardName' => '{class_name}',
                'cardType' => 'unknown',
                'usedByPlayerId' => $playerId,
                'usedByPlayerName' => $room['players'][$playerId]['name'] ?? 'Player',
                'targetPlayerId' => $playerId,
                'targetPlayerName' => $room['players'][$playerId]['name'] ?? 'Player',
                'note' => 'Efek {class_name} diaktifkan.',
            ]
        ];
    }}
}}
"""

for spell in spells:
    with open(os.path.join(base_dir, 'Spells', f"{spell}.php"), 'w') as f:
        f.write(get_template('App\\Services\\Cards\\Spells', spell))

for trap in traps:
    with open(os.path.join(base_dir, 'Traps', f"{trap}.php"), 'w') as f:
        f.write(get_template('App\\Services\\Cards\\Traps', trap))

print("Generated classes successfully.")
