<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use App\DTOs\SubmissionListQuery;
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
    public function listSubmissions(SubmissionListQuery $query): array
    {
        $where = $this->buildWhereClause($query);
        $sql = 'SELECT id, email, payload, ignored, follow_up_response, created_at,
                       auto_response_sent_at, follow_up_sent_at
                FROM submissions'
            . $where['sql']
            . $this->buildOrderClause($query)
            . ' LIMIT :limit OFFSET :offset';

        $stmt = $this->db->getPdo()->prepare($sql);
        foreach ($where['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $query->perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $query->offset(), PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['ignored'] = (bool) $row['ignored'];
            $row['payload'] = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);

            return $row;
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSubmissionsForExport(SubmissionListQuery $query, int $maxRows): array
    {
        $where = $this->buildWhereClause($query);
        $sql = 'SELECT id, email, payload, ignored, follow_up_response, created_at,
                       auto_response_sent_at, follow_up_sent_at
                FROM submissions'
            . $where['sql']
            . $this->buildOrderClause($query)
            . ' LIMIT :limit';

        $stmt = $this->db->getPdo()->prepare($sql);
        foreach ($where['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $maxRows, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['ignored'] = (bool) $row['ignored'];
            $row['payload'] = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);

            return $row;
        }, $rows);
    }

    public function countSubmissions(SubmissionListQuery $query): int
    {
        $where = $this->buildWhereClause($query);
        $sql = 'SELECT COUNT(*) FROM submissions' . $where['sql'];
        $stmt = $this->db->getPdo()->prepare($sql);
        foreach ($where['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{sql: string, params: array<string, string>}
     */
    private function buildWhereClause(SubmissionListQuery $query): array
    {
        $conditions = [];
        $params = [];

        if (!$query->includeIgnored) {
            $conditions[] = 'ignored = 0';
        }

        if ($query->status === 'new') {
            $conditions[] = 'ignored = 0';
            $conditions[] = 'follow_up_sent_at IS NULL';
        } elseif ($query->status === 'replied') {
            $conditions[] = 'follow_up_sent_at IS NOT NULL';
        }

        if ($query->search !== '') {
            $conditions[] = '(email LIKE :search'
                . " OR JSON_UNQUOTE(JSON_EXTRACT(payload, '$.question')) LIKE :search"
                . " OR JSON_UNQUOTE(JSON_EXTRACT(payload, '$.known_as')) LIKE :search)";
            $params[':search'] = '%' . $query->search . '%';
        }

        $sql = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return ['sql' => $sql, 'params' => $params];
    }

    private function buildOrderClause(SubmissionListQuery $query): string
    {
        $direction = $query->order === 'asc' ? 'ASC' : 'DESC';

        $primarySort = match ($query->sort) {
            'email' => 'email ' . $direction,
            'status' => 'CASE WHEN ignored = 1 THEN 2 WHEN follow_up_sent_at IS NOT NULL THEN 1 ELSE 0 END ' . $direction,
            default => 'created_at ' . $direction,
        };

        return ' ORDER BY ' . $primarySort . ', id DESC';
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
