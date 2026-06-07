<?php

declare(strict_types=1);

namespace App\Models;

readonly class PendingRegistration
{
    public function __construct(
        public int $id,
        public string $token,
        public string $username,
        public string $email,
        public string $password_hash,
        public string $password_reminder,
        public string $code_hash,
        public int $attempt_count,
        public int $resend_count,
        public string $last_sent_at,
        public string $expires_at,
        public string $created_at = '',
    ) {}
}
