<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AuthServicePasswordTest extends TestCase
{
    public function testChangePasswordUpdatesHashAndReminder(): void
    {
        $currentPassword = 'OldPass1';
        $newPassword = 'NewPass2';
        $reminder = 'Favorite color';
        $passwordHash = password_hash($currentPassword, PASSWORD_BCRYPT);

        $user = new User(
            id: 1,
            username: 'tester',
            email: 'tester@example.com',
            password_hash: $passwordHash,
            password_reminder: 'No hint',
        );

        $repo = $this->createMock(UserRepository::class);
        $repo->method('findById')->with(1)->willReturn($user);
        $repo->expects($this->once())
            ->method('updatePassword')
            ->with(
                1,
                $this->callback(fn (string $hash) => password_verify($newPassword, $hash)),
                $reminder,
            )
            ->willReturn(new User(
                id: 1,
                username: 'tester',
                email: 'tester@example.com',
                password_hash: password_hash($newPassword, PASSWORD_BCRYPT),
                password_reminder: $reminder,
            ));

        $service = new AuthService($repo, $this->createMock(LoggerInterface::class));
        $service->changePassword(1, $currentPassword, $newPassword, $reminder);

        $this->addToAssertionCount(1);
    }
}
