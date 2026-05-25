<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public function __construct(
        public readonly int $id = 0,
        public readonly string $username = '',
        public readonly string $email = '',
        private readonly string $password_hash = '',
        public readonly string $password_reminder = 'No hint',
        public readonly string $created_at = '',
        public readonly ?array $settings = null,
        public readonly ?string $deleted_at = null,
    ) {}

    public function isActive(): bool
    {
        return $this->deleted_at === null;
    }

    public function getPasswordHash(): string
    {
        return $this->password_hash;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'password_reminder' => $this->password_reminder,
            'created_at' => $this->created_at,
            'settings' => $this->settings,
            'deleted_at' => $this->deleted_at,
            'is_active' => $this->isActive(),
        ];
    }
}
