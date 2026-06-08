<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use App\DTOs\UserListQuery;
use App\Models\User;
use PDO;

class UserRepository
{
    public function __construct(
        private readonly Database $db,
    ) {}

    public function create(
        string $username,
        string $email,
        string $passwordHash,
        string $passwordReminder,
        ?array $settings = null,
    ): User {
        $settingsJson = $settings !== null ? json_encode($settings) : '{}';

        $stmt = $this->db->getPdo()->prepare(
            'INSERT INTO users (username, email, password_hash, password_reminder, settings)
             VALUES (:username, :email, :password_hash, :password_reminder, :settings)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':password_reminder' => $passwordReminder,
            ':settings' => $settingsJson,
        ]);

        $id = (int) $this->db->getPdo()->lastInsertId();

        return new User(
            id: $id,
            username: $username,
            email: $email,
            password_hash: $passwordHash,
            password_reminder: $passwordReminder,
            settings: $settings,
        );
    }

    public function updatePassword(int $userId, string $passwordHash, string $passwordReminder): User
    {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE users SET password_hash = :password_hash, password_reminder = :password_reminder WHERE id = :id'
        );
        $stmt->execute([
            ':password_hash' => $passwordHash,
            ':password_reminder' => $passwordReminder,
            ':id' => $userId,
        ]);

        $user = $this->findById($userId);
        if ($user === null) {
            throw new \RuntimeException('User not found after password update');
        }

        return $user;
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->getPdo()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM users WHERE username = :username AND deleted_at IS NULL'
        );
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM users WHERE email = :email AND deleted_at IS NULL'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByUsernameExcludingId(string $username, int $excludeId): ?User
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM users WHERE username = :username AND id != :id AND deleted_at IS NULL'
        );
        $stmt->execute([':username' => $username, ':id' => $excludeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByEmailExcludingId(string $email, int $excludeId): ?User
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM users WHERE email = :email AND id != :id AND deleted_at IS NULL'
        );
        $stmt->execute([':email' => $email, ':id' => $excludeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @return User[]
     */
    public function findAll(bool $includeInactive = false): array
    {
        $sql = 'SELECT * FROM users';
        if (!$includeInactive) {
            $sql .= ' WHERE deleted_at IS NULL';
        }
        $sql .= ' ORDER BY id ASC';

        $stmt = $this->db->getPdo()->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /**
     * @return User[]
     */
    public function findPaginated(UserListQuery $query): array
    {
        $where = $this->buildWhereClause($query);
        $sql = 'SELECT * FROM users'
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

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    public function countPaginated(UserListQuery $query): int
    {
        $where = $this->buildWhereClause($query);
        $sql = 'SELECT COUNT(*) FROM users' . $where['sql'];
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
    private function buildWhereClause(UserListQuery $query): array
    {
        $conditions = [];
        $params = [];

        if (!$query->includeInactive) {
            $conditions[] = 'deleted_at IS NULL';
        }

        if ($query->search !== '') {
            $conditions[] = '(username LIKE :search'
                . ' OR email LIKE :search'
                . " OR JSON_UNQUOTE(JSON_EXTRACT(settings, '$.user_alias')) LIKE :search)";
            $params[':search'] = '%' . $query->search . '%';
        }

        $sql = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return ['sql' => $sql, 'params' => $params];
    }

    private function buildOrderClause(UserListQuery $query): string
    {
        $direction = $query->order === 'asc' ? 'ASC' : 'DESC';

        $primarySort = match ($query->sort) {
            'email' => 'email ' . $direction,
            'user_alias' => "JSON_UNQUOTE(JSON_EXTRACT(settings, '$.user_alias')) " . $direction,
            default => 'username ' . $direction,
        };

        return ' ORDER BY ' . $primarySort . ', id ASC';
    }

    public function update(
        int $id,
        string $username,
        string $email,
        string $passwordHash,
        string $passwordReminder,
        array $settings,
    ): User {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE users SET username = :username, email = :email, password_hash = :password_hash,
             password_reminder = :password_reminder, settings = :settings WHERE id = :id'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':password_reminder' => $passwordReminder,
            ':settings' => json_encode($settings),
            ':id' => $id,
        ]);

        $user = $this->findById($id);
        if ($user === null) {
            throw new \RuntimeException('User not found after update');
        }

        return $user;
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): void
    {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE users SET deleted_at = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    private function hydrate(array $row): User
    {
        $settings = json_decode($row['settings'] ?? '{}', true);
        $deletedAt = $row['deleted_at'] ?? null;

        return new User(
            id: (int) $row['id'],
            username: $row['username'],
            email: $row['email'],
            password_hash: $row['password_hash'],
            password_reminder: $row['password_reminder'] ?? 'No hint',
            created_at: $row['created_at'] ?? '',
            settings: is_array($settings) ? $settings : null,
            deleted_at: $deletedAt !== null ? (string) $deletedAt : null,
        );
    }

    public function updateSettings(int $userId, array $partial): array
    {
        $user = $this->findById($userId);
        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        $current = $user->getSettings() ?? [];
        $merged = array_merge($current, $partial);

        $stmt = $this->db->getPdo()->prepare(
            'UPDATE users SET settings = :settings WHERE id = :id'
        );
        $stmt->execute([
            ':settings' => json_encode($merged),
            ':id' => $userId,
        ]);

        return $merged;
    }
}
