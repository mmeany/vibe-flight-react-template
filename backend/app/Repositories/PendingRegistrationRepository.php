<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use App\Models\PendingRegistration;
use PDO;

class PendingRegistrationRepository
{
    public function __construct(
        private readonly Database $db,
    ) {}

    public function findByToken(string $token): ?PendingRegistration
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM pending_registrations WHERE token = :token'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByUsername(string $username): ?PendingRegistration
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM pending_registrations WHERE username = :username'
        );
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByEmail(string $email): ?PendingRegistration
    {
        $stmt = $this->db->getPdo()->prepare(
            'SELECT * FROM pending_registrations WHERE email = :email'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function create(
        string $token,
        string $username,
        string $email,
        string $passwordHash,
        string $passwordReminder,
        string $codeHash,
        string $lastSentAt,
        string $expiresAt,
    ): PendingRegistration {
        $stmt = $this->db->getPdo()->prepare(
            'INSERT INTO pending_registrations
                (token, username, email, password_hash, password_reminder, code_hash, last_sent_at, expires_at)
             VALUES
                (:token, :username, :email, :password_hash, :password_reminder, :code_hash, :last_sent_at, :expires_at)'
        );
        $stmt->execute([
            ':token' => $token,
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':password_reminder' => $passwordReminder,
            ':code_hash' => $codeHash,
            ':last_sent_at' => $lastSentAt,
            ':expires_at' => $expiresAt,
        ]);

        $id = (int) $this->db->getPdo()->lastInsertId();

        return new PendingRegistration(
            id: $id,
            token: $token,
            username: $username,
            email: $email,
            password_hash: $passwordHash,
            password_reminder: $passwordReminder,
            code_hash: $codeHash,
            attempt_count: 0,
            resend_count: 0,
            last_sent_at: $lastSentAt,
            expires_at: $expiresAt,
        );
    }

    public function updateAfterResend(int $id, string $codeHash, string $lastSentAt, string $expiresAt, int $resendCount): void
    {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE pending_registrations
             SET code_hash = :code_hash, last_sent_at = :last_sent_at, expires_at = :expires_at,
                 resend_count = :resend_count, attempt_count = 0
             WHERE id = :id'
        );
        $stmt->execute([
            ':code_hash' => $codeHash,
            ':last_sent_at' => $lastSentAt,
            ':expires_at' => $expiresAt,
            ':resend_count' => $resendCount,
            ':id' => $id,
        ]);
    }

    public function incrementAttemptCount(int $id, int $attemptCount): void
    {
        $stmt = $this->db->getPdo()->prepare(
            'UPDATE pending_registrations SET attempt_count = :attempt_count WHERE id = :id'
        );
        $stmt->execute([
            ':attempt_count' => $attemptCount,
            ':id' => $id,
        ]);
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->db->getPdo()->prepare('DELETE FROM pending_registrations WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function deleteExpired(): void
    {
        $this->db->getPdo()->exec(
            'DELETE FROM pending_registrations WHERE expires_at < UTC_TIMESTAMP()'
        );
    }

    private function hydrate(array $row): PendingRegistration
    {
        return new PendingRegistration(
            id: (int) $row['id'],
            token: $row['token'],
            username: $row['username'],
            email: $row['email'],
            password_hash: $row['password_hash'],
            password_reminder: $row['password_reminder'],
            code_hash: $row['code_hash'],
            attempt_count: (int) $row['attempt_count'],
            resend_count: (int) $row['resend_count'],
            last_sent_at: (string) $row['last_sent_at'],
            expires_at: (string) $row['expires_at'],
            created_at: (string) ($row['created_at'] ?? ''),
        );
    }
}
