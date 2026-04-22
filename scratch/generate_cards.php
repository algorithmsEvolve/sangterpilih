<?php

$spells = [
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
];

$traps = [
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
];

$baseDir = __DIR__ . '/../app/Services/Cards';
@mkdir($baseDir . '/Spells', 0755, true);
@mkdir($baseDir . '/Traps', 0755, true);

// Create Interface
$interfaceCode = "<?php\n\nnamespace App\Services\Cards;\n\ninterface CardEffectInterface\n{\n    public function apply(array &\$room, string \$playerId, array \$data): ?array;\n}\n";
file_put_contents($baseDir . '/CardEffectInterface.php', $interfaceCode);

function getTemplate($namespace, $className) {
    return "<?php\n\nnamespace {$namespace};\n\nuse App\Services\Cards\CardEffectInterface;\n\nclass {$className} implements CardEffectInterface\n{\n    public function apply(array &\$room, string \$playerId, array \$data): ?array\n    {\n        // TODO: Implement logic\n        return [\n            'success' => true,\n            'payload' => [\n                'cardId' => 'unknown',\n                'cardName' => '{$className}',\n                'cardType' => 'unknown',\n                'usedByPlayerId' => \$playerId,\n                'usedByPlayerName' => \$room['players'][\$playerId]['name'] ?? 'Player',\n                'targetPlayerId' => \$playerId,\n                'targetPlayerName' => \$room['players'][\$playerId]['name'] ?? 'Player',\n                'note' => 'Efek {$className} diaktifkan.',\n            ]\n        ];\n    }\n}\n";
}

foreach ($spells as $spell) {
    file_put_contents($baseDir . '/Spells/' . $spell . '.php', getTemplate('App\Services\Cards\Spells', $spell));
}

foreach ($traps as $trap) {
    file_put_contents($baseDir . '/Traps/' . $trap . '.php', getTemplate('App\Services\Cards\Traps', $trap));
}

echo "Generated classes successfully.\n";
