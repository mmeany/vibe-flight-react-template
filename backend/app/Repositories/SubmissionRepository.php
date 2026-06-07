<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class SubmissionRepository
{
    public function __construct(
        private readonly Database $db,
    ) {}

    /**
     * @param array<string, string> $payload
     */
    public function insert(string $email, array $payload): int
    {
        $stmt = $this->db->getPdo()->prepare(
            'INSERT INTO submissions (email, payload) VALUES (:email, :payload)'
        );
        $stmt->execute([
            ':email' => $email,
            ':payload' => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        return (int) $this->db->getPdo()->lastInsertId();
    }

    public function markAutoResponseSent(int $id): void
    {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE submissions SET auto_response_sent_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSubmissions(bool $includeIgnored, int $limit, int $offset): array
    {
        $sql = 'SELECT id, email, payload, ignored, follow_up_response, created_at,
                       auto_response_sent_at, follow_up_sent_at
                FROM submissions';
        if (!$includeIgnored) {
            $sql .= ' WHERE ignored = 0';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['ignored'] = (bool) $row['ignored'];
            $row['payload'] = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);

            return $row;
        }, $rows);
    }

    public function countSubmissions(bool $includeIgnored): int
    {
        $sql = 'SELECT COUNT(*) FROM submissions';
        if (!$includeIgnored) {
            $sql .= ' WHERE ignored = 0';
        }

        return (int) $this->db->getPdo()->query($sql)->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT id, email, payload, ignored, follow_up_response, created_at,
                    auto_response_sent_at, follow_up_sent_at
             FROM submissions WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $row['id'] = (int) $row['id'];
        $row['ignored'] = (bool) $row['ignored'];
        $row['payload'] = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);

        return $row;
    }

    public function setIgnored(int $id, bool $ignored): bool
    {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE submissions SET ignored = :ignored WHERE id = :id'
        );
        $stmt->execute([
            ':ignored' => $ignored ? 1 : 0,
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function saveFollowUp(int $id, string $message): void
    {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE submissions SET follow_up_response = :message, follow_up_sent_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            ':message' => $message,
            ':id' => $id,
        ]);
    }
}
