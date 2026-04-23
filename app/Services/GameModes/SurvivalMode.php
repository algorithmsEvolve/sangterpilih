<?php

namespace App\Services\GameModes;

class SurvivalMode implements GameModeInterface
{
    public function getInitialScore(int $totalPlayers): int
    {
        return $totalPlayers < 4 ? 2000 : 3000;
    }

    public function processDiceRoll(array &$room, string $playerId, int $diceResult): array
    {
        $buffs = $room['players'][$playerId]['active_buffs'] ?? [];

        $rolls = [$diceResult];

        // 1. Diceroll Modifiers (Hanya untuk klik pertama/lemparan dadu murni)
        $isSubsequentRoll = isset($room['players'][$playerId]['pending_forced_roll']) || isset($room['players'][$playerId]['pending_rewind_roll']);
        
        if (!$isSubsequentRoll) {
            if (in_array('loaded_dice_low', $buffs)) {
                $rolls[0] = rand(1, 2);
            }
            if (in_array('reverse_fortune', $buffs)) {
                $rolls[0] = 7 - min(6, $rolls[0]); // ensures valid dice reverse
            }
            if (in_array('blindfold', $buffs)) {
                $rolls[0] += 1;
            }
        }

        // 2. Multi-Roll State Machine
        // Prioritas 1: Trap (Forced Reroll)
        if (in_array('forced_reroll', $buffs)) {
            if (!isset($room['players'][$playerId]['pending_forced_roll'])) {
                // Klik pertama Forced Reroll
                $room['players'][$playerId]['pending_forced_roll'] = $rolls[0];
                $room['players'][$playerId]['require_extra_roll'] = true;
                return $rolls;
            } else {
                // Klik kedua Forced Reroll
                $firstRoll = $room['players'][$playerId]['pending_forced_roll'];
                unset($room['players'][$playerId]['pending_forced_roll']);
                unset($room['players'][$playerId]['require_extra_roll']);
                
                $buffs = array_diff($buffs, ['forced_reroll']);
                $room['players'][$playerId]['active_buffs'] = array_values($buffs);
                
                $rolls = [$firstRoll, $rolls[0]];
            }
        } 
        // Prioritas 2: Spell (Rewind)
        elseif (in_array('rewind', $buffs)) {
            if (!isset($room['players'][$playerId]['pending_rewind_roll'])) {
                // Klik pertama Rewind
                $room['players'][$playerId]['pending_rewind_roll'] = $rolls[0];
                $room['players'][$playerId]['require_extra_roll'] = true;
                return $rolls;
            } else {
                // Klik kedua Rewind
                $firstRoll = $room['players'][$playerId]['pending_rewind_roll'];
                unset($room['players'][$playerId]['pending_rewind_roll']);
                unset($room['players'][$playerId]['require_extra_roll']);
                
                $buffs = array_diff($buffs, ['rewind']);
                $room['players'][$playerId]['active_buffs'] = array_values($buffs);
                
                // Rewind takes the minimum of the two rolls
                $rolls = [min($firstRoll, $rolls[0])];
            }
        }

        // Jika kombinasi (Forced Reroll selesai dan menghasilkan array [$first, $second]),
        // dan ternyata pemain juga punya Rewind yang belum diproses:
        if (in_array('rewind', $buffs) && count($rolls) > 1) {
            // Rewind otomatis mengambil yang terkecil dari hasil Forced Reroll
            $rolls = [min($rolls)];
            $buffs = array_diff($buffs, ['rewind']);
            $room['players'][$playerId]['active_buffs'] = array_values($buffs);
        }

        $totalDice = array_sum($rolls);
        $damage = $totalDice * 100;

        // 2. Damage Modifiers
        if (in_array('time_skip', $buffs)) {
            $damage = 0;
        }
        if (in_array('amnesty', $buffs)) {
            $damage = 0;
        }
        if (in_array('feather_fall', $buffs)) {
            $damage = min(100, $damage);
        }
        if (in_array('shield_of_grace', $buffs)) {
            $damage = $damage / 2;
        }
        if (in_array('curse_heavy_bones', $buffs)) {
            $damage *= 2;
        }
        if (in_array('gamblers_shield_odd', $buffs)) {
            $damage = ($totalDice % 2 !== 0) ? 0 : $damage * 2;
        }
        if (in_array('gamblers_shield_even', $buffs)) {
            $damage = ($totalDice % 2 === 0) ? 0 : $damage * 2;
        }

        // 3. Special Damage Actions
        if (in_array('reflect_stance', $buffs) && $totalDice >= 5) {
            $otherPlayers = array_filter(array_keys($room['players']), fn($id) => $id !== $playerId);
            if (!empty($otherPlayers)) {
                $damagePerPlayer = floor($damage / count($otherPlayers));
                foreach ($otherPlayers as $otherId) {
                    $room['players'][$otherId]['score'] -= $damagePerPlayer;
                }
            }
            $damage = 0; // You take 0
        }
        
        $karmaTarget = null;
        foreach ($buffs as $buff) {
            if (str_starts_with($buff, 'karma:')) {
                $karmaTarget = explode(':', $buff)[1];
                break;
            }
        }
        if ($karmaTarget && $damage > 400 && isset($room['players'][$karmaTarget])) {
            $room['players'][$karmaTarget]['score'] -= $damage;
            $damage = 0; // Reflected
        }

        if (in_array('vampiric_aura', $buffs)) {
            $damage = -$damage; // Heal
        }

        // Apply Damage
        $room['players'][$playerId]['score'] -= $damage;

        // 4. Post-Damage Modifiers
        if (in_array('last_stand', $buffs) && $room['players'][$playerId]['score'] <= 0) {
            $room['players'][$playerId]['score'] = 1;
        }

        if (in_array('adrenaline_rush', $buffs)) {
            // Need to pass to next turn, so we convert it to a heal buff
            $room['players'][$playerId]['active_buffs'] = ['adrenaline_heal:' . ($damage * 2)];
        } else {
            // Check if there's an adrenaline heal from last turn
            $healAmount = 0;
            foreach ($buffs as $buff) {
                if (str_starts_with($buff, 'adrenaline_heal:')) {
                    $healAmount = (int) explode(':', $buff)[1];
                    $room['players'][$playerId]['score'] += $healAmount;
                    break;
                }
            }
            
            // Check time bombs
            $newBuffs = [];
            foreach ($buffs as $buff) {
                if (str_starts_with($buff, 'time_bomb:')) {
                    $turnsLeft = (int) explode(':', $buff)[1];
                    $turnsLeft--;
                    if ($turnsLeft <= 0) {
                        $room['players'][$playerId]['score'] -= 800; // Explode!
                    } else {
                        $newBuffs[] = "time_bomb:$turnsLeft"; // Keep it
                    }
                }
                // Keep sabotaged? Sabotaged lasts 1 round, but we can clear it here since they rolled.
                // Wait, if they are sabotaged, they can't use spells THIS turn. So it clears after roll.
            }
            
            // Clear buffs that only last 1 roll (everything except what we explicitly kept)
            $room['players'][$playerId]['active_buffs'] = $newBuffs;
        }
        
        return $rolls;
    }

    public function checkGameOverCondition(array $room): bool
    {
        // Survival mode ends if ANY player's LP drops to 0 or below
        foreach ($room['players'] as $player) {
            if ($player['score'] <= 0) {
                return true;
            }
        }
        return false;
    }
}
