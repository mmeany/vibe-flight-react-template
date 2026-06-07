<?php

declare(strict_types=1);

namespace App\Tests;

use App\Config\AppConfig;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Repositories\PendingRegistrationRepository;
use App\Repositories\UserRepository;
use App\Services\ChallengeService;
use App\Services\MailService;
use App\Services\RateLimitService;
use App\Services\RegistrationService;
use App\Utils\VerificationCodeUtil;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RegistrationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['REGISTRATION_ENABLED'] = 'true';
        $_ENV['JWT_SECRET'] = 'test-secret-key-for-jwt-signing-32';
        $_ENV['ADMIN_NOTIFY_EMAIL'] = 'admin@example.com';
        $this->resetAppConfig();
        AppConfig::load();
    }

    public function testStartRegistrationReturnsPendingToken(): void
    {
        $pendingRepo = $this->createMock(PendingRegistrationRepository::class);
        $pendingRepo->expects($this->once())->method('deleteExpired');
        $pendingRepo->expects($this->once())->method('findByUsername')->willReturn(null);
        $pendingRepo->expects($this->once())->method('findByEmail')->willReturn(null);
        $pendingRepo->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (
                string $token,
                string $username,
                string $email,
            ) {
                return new PendingRegistration(
                    id: 1,
                    token: $token,
                    username: $username,
                    email: $email,
                    password_hash: 'hash',
                    password_reminder: 'hint',
                    code_hash: 'code-hash',
                    attempt_count: 0,
                    resend_count: 0,
                    last_sent_at: gmdate('Y-m-d H:i:s'),
                    expires_at: gmdate('Y-m-d H:i:s', time() + 900),
                );
            });

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByUsername')->willReturn(null);
        $userRepo->method('findByEmail')->willReturn(null);

        $mailService = $this->createMock(MailService::class);
        $mailService->expects($this->once())->method('sendVerificationCode')->willReturn(true);

        $rateLimit = $this->createMock(RateLimitService::class);
        $rateLimit->expects($this->once())->method('enforce');

        $challenge = $this->createMock(ChallengeService::class);
        $challenge->expects($this->once())->method('verify');

        $service = new RegistrationService(
            $pendingRepo,
            $userRepo,
            $mailService,
            $rateLimit,
            $challenge,
            $this->createMock(LoggerInterface::class),
        );

        $result = $service->startRegistration(
            'newuser',
            'new@example.com',
            'Password1',
            'hint',
            '',
            'token',
            '42',
            time() - 10,
        );

        $this->assertArrayHasKey('pending_token', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertNotSame('', $result['pending_token']);
    }

    public function testVerifyRegistrationCreatesUserAndReturnsToken(): void
    {
        $code = '123456';
        $pending = new PendingRegistration(
            id: 1,
            token: 'pending-token',
            username: 'newuser',
            email: 'new@example.com',
            password_hash: password_hash('Password1', PASSWORD_BCRYPT),
            password_reminder: 'hint',
            code_hash: VerificationCodeUtil::hash($code),
            attempt_count: 0,
            resend_count: 0,
            last_sent_at: gmdate('Y-m-d H:i:s'),
            expires_at: gmdate('Y-m-d H:i:s', time() + 900),
        );

        $createdUser = new User(
            id: 42,
            username: 'newuser',
            email: 'new@example.com',
            password_hash: $pending->password_hash,
            password_reminder: 'hint',
            settings: ['theme_mode' => 'light'],
        );

        $pendingRepo = $this->createMock(PendingRegistrationRepository::class);
        $pendingRepo->method('findByToken')->with('pending-token')->willReturn($pending);
        $pendingRepo->expects($this->once())->method('deleteById')->with(1);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())->method('create')->willReturn($createdUser);

        $mailService = $this->createMock(MailService::class);
        $mailService->expects($this->once())
            ->method('sendAdminNewUserNotification')
            ->with(['admin@example.com'], 'newuser', 'new@example.com')
            ->willReturn(true);

        $service = new RegistrationService(
            $pendingRepo,
            $userRepo,
            $mailService,
            $this->createMock(RateLimitService::class),
            $this->createMock(ChallengeService::class),
            $this->createMock(LoggerInterface::class),
        );

        $result = $service->verifyRegistration('pending-token', $code);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame('newuser', $result['user']['username']);
    }

    private function resetAppConfig(): void
    {
        $ref = new \ReflectionClass(AppConfig::class);
        $loaded = $ref->getProperty('loaded');
        $loaded->setAccessible(true);
        $loaded->setValue(null, false);
    }
}
