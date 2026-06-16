<?php

namespace App\Services\Game;

class CardManager
{
    public const CARD_SKIP = 'skip_si';
    public const CARD_MULTIPLIER = 'multiplier';

    public function cardCatalog(): array
    {
        $oldCards = [
            self::CARD_SKIP => [
                'id' => self::CARD_SKIP,
                'name' => 'Sekip si',
                'type' => 'trap',
                'color' => 'red',
                'price' => 5,
                'image' => 'seseorang yang mengacuhkan orang lain',
                'image_url' => '
                    <svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
                        <defs>
                            <linearGradient id="bg" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="#7f1d1d"/>
                            <stop offset="100%" stop-color="#450a0a"/>
                            </linearGradient>
                        </defs>
                        <rect width="512" height="512" fill="url(#bg)"/>
                        <circle cx="170" cy="190" r="64" fill="#fca5a5"/>
                        <rect x="120" y="250" width="100" height="150" rx="26" fill="#ef4444"/>
                        <circle cx="345" cy="180" r="62" fill="#fecaca"/>
                        <rect x="302" y="245" width="92" height="145" rx="24" fill="#6b7280"/>
                        <line x1="66" y1="88" x2="445" y2="420" stroke="#fef2f2" stroke-width="24" stroke-linecap="round"/>
                        <text x="34" y="476" fill="#fff1f2" font-size="44" font-family="Arial, sans-serif" font-weight="700">SEKIP SI</text>
                    </svg>
                ',
                'description' => 'Skip giliran player aktif. Kalo dia udah lempar dadu, poinnya dibalikin kayak belum lempar. Brutal tapi fair.',
            ],
            self::CARD_MULTIPLIER => [
                'id' => self::CARD_MULTIPLIER,
                'name' => 'Multipler',
                'type' => 'spell',
                'color' => 'green',
                'price' => 8,
                'image' => 'tulisan 8x8 6x4 dicoret lalu ada x2',
                'image_url' => '
                    <svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
                        <defs>
                            <linearGradient id="bg2" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="#064e3b"/>
                            <stop offset="100%" stop-color="#022c22"/>
                            </linearGradient>
                        </defs>
                        <rect width="512" height="512" fill="url(#bg2)"/>
                        <text x="64" y="180" fill="#a7f3d0" font-size="84" font-family="Arial, sans-serif" font-weight="700">8x8</text>
                        <text x="64" y="272" fill="#a7f3d0" font-size="84" font-family="Arial, sans-serif" font-weight="700">6x4</text>
                        <line x1="52" y1="120" x2="340" y2="320" stroke="#ef4444" stroke-width="16" stroke-linecap="round"/>
                        <text x="286" y="262" fill="#ecfeff" font-size="118" font-family="Arial, sans-serif" font-weight="900">x2</text>
                        <text x="30" y="476" fill="#d1fae5" font-size="44" font-family="Arial, sans-serif" font-weight="700">MULTIPLER</text>
                    </svg>
                ',
                'description' => 'Aktifin di giliran lo, sebelum lempar. Hasil dadu lo jadi x2. Gaspol!',
            ],
        ];

        $spells = config('cards.spells', []);
        $traps = config('cards.traps', []);

        $merged = array_merge($oldCards, $spells, $traps);

        return array_map(function ($card) {
            $card['not_available'] = (int) ($card['not_available'] ?? 0);
            return $card;
        }, $merged);
    }

    /**
     * Apply card effect. Returns array with keys:
     * - 'error' => string (jika gagal)
     * - 'effectPayload' => array, 'advanceTurn' => bool, 'forceDiceRollEvent' => mixed (jika sukses)
     */
    public function applyCardEffect(array &$room, string $playerId, string $cardId, array $requestData, array $catalog): array
    {
        $effectClass = $catalog[$cardId]['effect_class'] ?? null;
        $effectPayload = null;
        $playerName = $room['players'][$playerId]['name'];
        $advanceTurn = false;
        $forceDiceRollEvent = null;

        if ($effectClass && class_exists($effectClass)) {
            $effect = app($effectClass);
            $result = $effect->apply($room, $playerId, $requestData);

            if (isset($result['error'])) {
                return ['error' => $result['error']];
            }

            $effectPayload = $result['payload'] ?? null;
            if ($effectPayload) {
                // Ensure card details are filled if missing
                $effectPayload['cardId'] = $cardId;
                $effectPayload['cardName'] = $catalog[$cardId]['name'];
                $effectPayload['cardType'] = $catalog[$cardId]['type'];
                $effectPayload['usedByPlayerId'] = $effectPayload['usedByPlayerId'] ?? $playerId;
                $effectPayload['usedByPlayerName'] = $effectPayload['usedByPlayerName'] ?? $playerName;
                $targetPlayerId = $effectPayload['targetPlayerId'] ?? $requestData['target_player_id'] ?? null;
                if ($targetPlayerId && isset($room['players'][$targetPlayerId])) {
                    $effectPayload['targetPlayerId'] = $targetPlayerId;
                    $effectPayload['targetPlayerName'] = $effectPayload['targetPlayerName'] ?? $room['players'][$targetPlayerId]['name'];
                }
                if (isset($requestData['is_random']) && $requestData['is_random']) {
                    $effectPayload['isRandom'] = true;
                }
            }
            $advanceTurn = !empty($result['advance_turn']);
            $forceDiceRollEvent = $result['force_dice_roll_event'] ?? null;
        } else {
            // Fallback for hardcoded cards
            if ($cardId === self::CARD_MULTIPLIER) {
                if ($room['current_turn_player_id'] !== $playerId) {
                    return ['error' => 'Spell multiplier cuma bisa dipakai saat giliran lo.'];
                }

                if ($room['turn_multiplier_player_id'] === $playerId) {
                    return ['error' => 'Multiplier lo udah aktif di giliran ini.'];
                }

                if ($room['players'][$playerId]['has_rolled_this_turn']) {
                    $startScore = $room['active_turn_snapshot']['start_score'] ?? $room['players'][$playerId]['score'];
                    $turnGain = max(0, $room['players'][$playerId]['score'] - $startScore);
                    
                    if ($turnGain <= 0) {
                        return ['error' => 'Belum ada hasil roll yang bisa digandain.'];
                    }
                    
                    $room['players'][$playerId]['score'] = $startScore + ($turnGain * 2);
                    if ($room['last_dice_result']) {
                        $room['last_dice_result'] *= 2;
                    }
                    $room['turn_multiplier_player_id'] = null;
                    
                    $effectPayload = [
                        'cardId' => $cardId,
                        'cardName' => $catalog[$cardId]['name'],
                        'cardType' => $catalog[$cardId]['type'],
                        'usedByPlayerId' => $playerId,
                        'usedByPlayerName' => $playerName,
                        'targetPlayerId' => $playerId,
                        'targetPlayerName' => $playerName,
                        'note' => $playerName . ' nge-boost hasil dadu jadi x2.',
                    ];
                } else {
                    $room['turn_multiplier_player_id'] = $playerId;
                    $effectPayload = [
                        'cardId' => $cardId,
                        'cardName' => $catalog[$cardId]['name'],
                        'cardType' => $catalog[$cardId]['type'],
                        'usedByPlayerId' => $playerId,
                        'usedByPlayerName' => $playerName,
                        'targetPlayerId' => $playerId,
                        'targetPlayerName' => $playerName,
                        'note' => $playerName . ' siapin multiplier. Roll berikutnya bakal x2.',
                    ];
                }
            } elseif ($cardId === self::CARD_SKIP) {
                if ($room['current_turn_player_id'] === $playerId) {
                    return ['error' => 'Trap skip dipakai buat ngerjain orang lain, bukan diri sendiri.'];
                }

                if ($room['turn_has_skip']) {
                    return ['error' => 'Skip udah kepake di giliran ini, gak bisa dobel.'];
                }

                $targetId = $room['current_turn_player_id'];
                $targetName = null;
                
                if (isset($room['players'][$targetId])) {
                    $targetName = $room['players'][$targetId]['name'];
                    $startScore = $room['active_turn_snapshot']['start_score'] ?? null;
                    
                    if ($room['active_turn_snapshot']['player_id'] === $targetId && $startScore !== null) {
                        $room['players'][$targetId]['score'] = $startScore;
                        $room['players'][$targetId]['has_rolled_this_turn'] = false;
                        $room['last_dice_result'] = null;
                        $room['last_roller_name'] = null;
                    }
                }

                $room['turn_has_skip'] = true;
                $effectPayload = [
                    'cardId' => $cardId,
                    'cardName' => $catalog[$cardId]['name'],
                    'cardType' => $catalog[$cardId]['type'],
                    'usedByPlayerId' => $playerId,
                    'usedByPlayerName' => $playerName,
                    'targetPlayerId' => $targetId,
                    'targetPlayerName' => $targetName,
                    'note' => $playerName . ' ngeskip giliran ' . ($targetName ?? 'target') . '. Sadis!',
                ];
            } else {
                return ['error' => 'Efek kartu ini belum siap dipakai!'];
            }
        }

        return [
            'effectPayload' => $effectPayload,
            'advanceTurn' => $advanceTurn,
            'forceDiceRollEvent' => $forceDiceRollEvent,
        ];
    }

    public function removeCardFromInventory(array &$room, string $playerId, string $cardId): void
    {
        $invAfter = $room['players'][$playerId]['inventory'] ?? [];
        $removeIdx = array_search($cardId, $invAfter, true);
        if ($removeIdx !== false) {
            unset($invAfter[$removeIdx]);
            $room['players'][$playerId]['inventory'] = array_values($invAfter);
        }
    }
}
