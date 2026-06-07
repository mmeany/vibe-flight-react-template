<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class RateLimitRepository
{
    public function __construct(
        private readonly Database $db,
    ) {}

    /**
     * @return array{count: int, window_start: ?string}
     */
    public function getForUpdate(string $keyType, string $keyValue, string $windowType): array
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT count, window_start FROM rate_limits
             WHERE key_type = :key_type AND key_value = :key_value AND window_type = :window_type
             FOR UPDATE'
        );
        $stmt->execute([
            ':key_type' => $keyType,
            ':key_value' => $keyValue,
            ':window_type' => $windowType,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return ['count' => 0, 'window_start' => null];
        }

        return [
            'count' => (int) $row['count'],
            'window_start' => $row['window_start'],
        ];
    }

    public function upsert(string $keyType, string $keyValue, string $windowType, int $count, ?string $windowStart): void
    {
        $stmt = $this->db->getPdo()->prepare(
            'INSERT INTO rate_limits (key_type, key_value, window_type, count, window_start)
             VALUES (:key_type, :key_value, :window_type, :count, :window_start)
             ON DUPLICATE KEY UPDATE count = :count_update, window_start = :window_start_update'
        );
        $stmt->execute([
            ':key_type' => $keyType,
            ':key_value' => $keyValue,
            ':window_type' => $windowType,
            ':count' => $count,
            ':window_start' => $windowStart,
            ':count_update' => $count,
            ':window_start_update' => $windowStart,
        ]);
    }
}
