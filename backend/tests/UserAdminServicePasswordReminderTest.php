<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserAdminService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserAdminServicePasswordReminderTest extends TestCase
{
    public function testCreateUserWithoutExitStoresPasswordReminder(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByUsername')->willReturn(null);
        $repo->method('findByEmail')->willReturn(null);
        $repo->expects($this->once())
            ->method('create')
            ->with(
                'alice',
                'alice@example.com',
                $this->anything(),
                'Favorite park bench',
                $this->anything(),
            )
            ->willReturn(new User(
                id: 1,
                username: 'alice',
                email: 'alice@example.com',
                password_reminder: 'Favorite park bench',
            ));

        $service = new UserAdminService($repo, $this->createMock(LoggerInterface::class));
        $user = $this->invokeCreateUserWithoutExit(
            $service,
            'alice',
            'alice@example.com',
            'Password1',
            'Favorite park bench',
        );

        $this->assertSame('Favorite park bench', $user->password_reminder);
    }

    public function testCreateUserWithoutExitRejectsEmptyPasswordReminder(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $service = new UserAdminService($repo, $this->createMock(LoggerInterface::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password reminder is required.');

        $this->invokeCreateUserWithoutExit(
            $service,
            'alice',
            'alice@example.com',
            'Password1',
            '   ',
        );
    }

    public function testUpdateUserUpdatesPasswordReminderWhenProvided(): void
    {
        $existing = new User(
            id: 2,
            username: 'bob',
            email: 'bob@example.com',
            password_hash: password_hash('Password1', PASSWORD_BCRYPT),
            password_reminder: 'No hint',
            settings: ['user_alias' => 'bob'],
        );

        $repo = $this->createMock(UserRepository::class);
        $repo->method('findById')->willReturn($existing);
        $repo->method('findByUsernameExcludingId')->willReturn(null);
        $repo->method('findByEmailExcludingId')->willReturn(null);
        $repo->expects($this->once())
            ->method('update')
            ->with(
                2,
                'bob',
                'bob@example.com',
                $existing->getPasswordHash(),
                'Childhood street',
                ['user_alias' => 'bob'],
            )
            ->willReturn(new User(
                id: 2,
                username: 'bob',
                email: 'bob@example.com',
                password_reminder: 'Childhood street',
            ));

        $service = new UserAdminService($repo, $this->createMock(LoggerInterface::class));
        $updated = $service->updateUser(2, 'bob', 'bob@example.com', null, null, 'Childhood street', 1);

        $this->assertSame('Childhood street', $updated->password_reminder);
    }

    private function invokeCreateUserWithoutExit(
        UserAdminService $service,
        string $username,
        string $email,
        string $password,
        string $passwordReminder,
    ): User {
        $method = new \ReflectionMethod(UserAdminService::class, 'createUserWithoutExit');
        $method->setAccessible(true);

        return $method->invoke($service, $username, $email, $password, $passwordReminder, null);
    }
}
