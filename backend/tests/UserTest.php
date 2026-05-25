<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testToArrayIncludesSettings(): void
    {
        $user = new User(
            id: 1,
            username: 'testuser',
            email: 'test@example.com',
            password_hash: 'hash',
            created_at: '2024-01-01',
            settings: ['theme_mode' => 'dark', 'date_format' => 'DD/MM/YYYY', 'user_alias' => 'Test'],
        );

        $array = $user->toArray();
        $this->assertArrayHasKey('settings', $array);
        $this->assertSame('dark', $array['settings']['theme_mode']);
        $this->assertSame('DD/MM/YYYY', $array['settings']['date_format']);
        $this->assertSame('Test', $array['settings']['user_alias']);
        $this->assertArrayNotHasKey('password_hash', $array);
        $this->assertSame('No hint', $array['password_reminder']);
    }

    public function testSettingsDefaultToNull(): void
    {
        $user = new User(
            id: 1,
            username: 'testuser',
            email: 'test@example.com',
        );

        $this->assertNull($user->getSettings());
    }

    public function testGetSettings(): void
    {
        $settings = ['theme_mode' => 'light'];
        $user = new User(
            id: 1,
            username: 'testuser',
            email: 'test@example.com',
            settings: $settings,
        );

        $this->assertSame($settings, $user->getSettings());
    }
}