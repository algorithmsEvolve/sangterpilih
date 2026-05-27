<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

class RoomRedisRepository
{
    private const TTL = 7200; // Room hangus otomatis dalam 2 jam (7200 detik)

    /**
     * Ambil state utuh Room dari Redis.
     */
    public static function getRoom(string $code): ?array
    {
        $data = Redis::get("room:{$code}");
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Simpan state utuh Room kembali ke Redis dan perbarui waktu hangus (Self-Destruct).
     */
    public static function saveRoom(string $code, array $state): void
    {
        Redis::setex("room:{$code}", self::TTL, json_encode($state));
    }

    /**
     * Hapus Room dari memori secara permanen.
     */
    public static function deleteRoom(string $code): void
    {
        Redis::del("room:{$code}");
    }

    /**
     * Ambil ringkasan room yang masih bisa di-join.
     */
    public static function listAvailableRooms(): array
    {
        $keys = Redis::keys('*room:*') ?: [];
        $rooms = [];

        foreach ($keys as $key) {
            $key = (string) $key;
            $roomPos = strpos($key, 'room:');
            if ($roomPos === false) {
                continue;
            }

            $code = substr($key, $roomPos + strlen('room:'));
            $room = self::getRoom($code);
            if (!$room || ($room['status'] ?? null) !== 'waiting') {
                continue;
            }

            $players = array_values($room['players'] ?? []);
            $host = collect($players)->firstWhere('is_host', true);

            $rooms[] = [
                'code' => $room['code'],
                'mode' => $room['mode'] ?? 'classic',
                'status' => $room['status'],
                'playerCount' => count($players),
                'hostName' => $host['name'] ?? ($players[0]['name'] ?? '-'),
            ];
        }

        usort($rooms, function ($a, $b) {
            return strcmp($a['code'], $b['code']);
        });

        return $rooms;
    }

    /**
     * Helper: Format awal struktur Room baru.
     */
    public static function buildInitialRoom(string $code, string $hostId, string $hostName, string $mode = 'classic'): array
    {
        return [
            'code' => $code,
            'mode' => $mode,
            'status' => 'waiting',
            'current_turn_player_id' => $hostId,
            'current_round' => 1,
            'total_rounds' => 5, // Standar
            'turn_index' => 0,
            'turn_has_skip' => false,
            'turn_multiplier_player_id' => null,
            'last_dice_result' => null,
            'last_roller_name' => null,
            'active_turn_snapshot' => null,
            'pending_trap_confirmations' => [],
            'trap_target_player_id' => null,
            'players' => [
                $hostId => [
                    'id' => $hostId,
                    'name' => $hostName,
                    'is_host' => true,
                    'score' => 0,
                    'has_rolled_this_turn' => false,
                    'inventory' => [],
                ]
            ],
        ];
    }
}
