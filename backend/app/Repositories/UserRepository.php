<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use App\Models\User;
use PDO;

class UserRepository
{
    public function __construct(
        private readonly Database $db,
    ) {}

    public function create(string $username, string $email, string $passwordHash, ?array $settings = null): User
    {
        $settingsJson = $settings !== null ? json_encode($settings) : '{}';

        $stmt = $this->db->getPdo()->prepare(
            'INSERT INTO users (username, email, password_hash, settings) VALUES (:username, :email, :password_hash, :settings)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':settings' => $settingsJson,
        ]);

        $id = (int) $this->db->getPdo()->lastInsertId();

        return new User(
            id: $id,
            username: $username,
            email: $email,
            password_hash: $passwordHash,
            settings: $settings,
        );
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

    public function update(
        int $id,
        string $username,
        string $email,
        string $passwordHash,
        array $settings,
    ): User {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE users SET username = :username, email = :email, password_hash = :password_hash, settings = :settings WHERE id = :id'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
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
