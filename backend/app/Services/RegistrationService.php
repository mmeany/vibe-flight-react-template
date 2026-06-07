<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use App\Http\Response;
use App\Repositories\PendingRegistrationRepository;
use App\Repositories\UserRepository;
use App\Support\ClientIp;
use App\Utils\JwtUtil;
use App\Utils\UserValidation;
use App\Utils\VerificationCodeUtil;
use Psr\Log\LoggerInterface;

class RegistrationService
{
    private const CODE_EXPIRY_MINUTES = 15;
    private const MAX_ATTEMPTS = 5;
    private const MAX_RESENDS = 3;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly PendingRegistrationRepository $pendingRepository,
        private readonly UserRepository $userRepository,
        private readonly MailService $mailService,
        private readonly RateLimitService $rateLimitService,
        private readonly ChallengeService $challengeService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array{pending_token: string, expires_at: string}
     */
    public function startRegistration(
        string $username,
        string $email,
        string $password,
        string $passwordReminder,
        string $website,
        string $challengeToken,
        string $challengeAnswer,
        int $formLoadedAt,
    ): array {
        if (!AppConfig::isRegistrationEnabled()) {
            Response::forbidden('Registration is currently disabled');
            exit;
        }

        if ($website !== '') {
            $this->logger->info('honeypot_triggered', ['event' => 'honeypot_triggered', 'context' => 'registration']);
            Response::unprocessableEntity('Unable to process your request.');
            exit;
        }

        $errors = UserValidation::validateRegistration($username, $email, $password, $passwordReminder);
        if ($errors !== []) {
            Response::unprocessableEntity(implode(' ', $errors));
            exit;
        }

        try {
            $this->challengeService->verify($challengeToken, $challengeAnswer, $formLoadedAt);
        } catch (\InvalidArgumentException $e) {
            Response::unprocessableEntity($e->getMessage());
            exit;
        }

        $ip = ClientIp::resolve();

        try {
            $this->rateLimitService->enforce(strtolower($email), $ip);
        } catch (\RuntimeException $e) {
            Response::tooManyRequests($e->getMessage());
            exit;
        }

        $this->pendingRepository->deleteExpired();
        $this->assertUsernameAvailable($username);
        $this->assertEmailAvailable($email);

        $token = $this->generateToken();
        $code = VerificationCodeUtil::generate();
        $codeHash = VerificationCodeUtil::hash($code);
        $passwordHash = UserValidation::hashPassword($password);
        $now = time();
        $lastSentAt = gmdate('Y-m-d H:i:s', $now);
        $expiresAt = gmdate('Y-m-d H:i:s', $now + self::CODE_EXPIRY_MINUTES * 60);

        $pending = $this->pendingRepository->create(
            $token,
            $username,
            $email,
            $passwordHash,
            trim($passwordReminder),
            $codeHash,
            $lastSentAt,
            $expiresAt,
        );

        $sent = $this->mailService->sendVerificationCode($email, $username, $code);
        if (!$sent) {
            $this->pendingRepository->deleteById($pending->id);
            Response::serviceUnavailable('Could not send verification email. Please try again later.');
            exit;
        }

        $this->logger->info('registration.started', [
            'event' => 'registration.started',
            'username' => $username,
            'email' => $email,
        ]);

        return [
            'pending_token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function verifyRegistration(string $pendingToken, string $code): array
    {
        if (!AppConfig::isRegistrationEnabled()) {
            Response::forbidden('Registration is currently disabled');
            exit;
        }

        $pending = $this->pendingRepository->findByToken($pendingToken);
        if ($pending === null || $this->isExpired($pending->expires_at)) {
            if ($pending !== null) {
                $this->pendingRepository->deleteById($pending->id);
            }
            Response::gone('Verification expired. Please start registration again.');
            exit;
        }

        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            Response::validationError('Verification code must be 6 digits.');
            exit;
        }

        if (!VerificationCodeUtil::verify($code, $pending->code_hash)) {
            $newAttemptCount = $pending->attempt_count + 1;
            if ($newAttemptCount >= self::MAX_ATTEMPTS) {
                $this->pendingRepository->deleteById($pending->id);
                Response::gone('Too many incorrect attempts. Please start registration again.');
                exit;
            }

            $this->pendingRepository->incrementAttemptCount($pending->id, $newAttemptCount);
            $remaining = self::MAX_ATTEMPTS - $newAttemptCount;
            Response::validationError(
                "Incorrect verification code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . ' remaining.'
            );
            exit;
        }

        $settings = [
            'theme_mode' => 'light',
            'date_format' => 'MM/DD/YYYY',
            'user_alias' => $pending->username,
        ];

        $user = $this->userRepository->create(
            $pending->username,
            $pending->email,
            $pending->password_hash,
            $pending->password_reminder,
            $settings,
        );

        $this->pendingRepository->deleteById($pending->id);

        $adminEmails = AppConfig::getAdminNotifyEmails();
        if ($adminEmails !== []) {
            $notified = $this->mailService->sendAdminNewUserNotification(
                $adminEmails,
                $pending->username,
                $pending->email,
            );
            $this->logger->info('registration.admin_notify', [
                'event' => 'registration.admin_notify',
                'username' => $pending->username,
                'sent' => $notified,
            ]);
        }

        $token = JwtUtil::generateToken($user->id, $user->username);
        $userData = $user->toArray();
        $userData['is_admin'] = AppConfig::isAdminUsername($user->username);

        $this->logger->info('registration.completed', [
            'event' => 'registration.completed',
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        return [
            'token' => $token,
            'user' => $userData,
        ];
    }

    /**
     * @return array{expires_at: string}
     */
    public function resendVerification(string $pendingToken): array
    {
        if (!AppConfig::isRegistrationEnabled()) {
            Response::forbidden('Registration is currently disabled');
            exit;
        }

        $pending = $this->pendingRepository->findByToken($pendingToken);
        if ($pending === null || $this->isExpired($pending->expires_at)) {
            if ($pending !== null) {
                $this->pendingRepository->deleteById($pending->id);
            }
            Response::gone('Verification expired. Please start registration again.');
            exit;
        }

        if ($pending->resend_count >= self::MAX_RESENDS) {
            Response::tooManyRequests('Maximum resend limit reached. Please start registration again.');
            exit;
        }

        $lastSentTs = strtotime($pending->last_sent_at);
        if ($lastSentTs !== false && (time() - $lastSentTs) < self::RESEND_COOLDOWN_SECONDS) {
            Response::tooManyRequests('Please wait before requesting another code.');
            exit;
        }

        $ip = ClientIp::resolve();

        try {
            $this->rateLimitService->enforce(strtolower($pending->email), $ip);
        } catch (\RuntimeException $e) {
            Response::tooManyRequests($e->getMessage());
            exit;
        }

        $code = VerificationCodeUtil::generate();
        $codeHash = VerificationCodeUtil::hash($code);
        $now = time();
        $lastSentAt = gmdate('Y-m-d H:i:s', $now);
        $expiresAt = gmdate('Y-m-d H:i:s', $now + self::CODE_EXPIRY_MINUTES * 60);
        $newResendCount = $pending->resend_count + 1;

        $sent = $this->mailService->sendVerificationCode($pending->email, $pending->username, $code);
        if (!$sent) {
            Response::serviceUnavailable('Could not send verification email. Please try again later.');
            exit;
        }

        $this->pendingRepository->updateAfterResend(
            $pending->id,
            $codeHash,
            $lastSentAt,
            $expiresAt,
            $newResendCount,
        );

        $this->logger->info('registration.resend', [
            'event' => 'registration.resend',
            'username' => $pending->username,
            'resend_count' => $newResendCount,
        ]);

        return ['expires_at' => $expiresAt];
    }

    private function assertUsernameAvailable(string $username): void
    {
        if ($this->userRepository->findByUsername($username) !== null) {
            Response::conflict('Username already taken');
            exit;
        }

        $pending = $this->pendingRepository->findByUsername($username);
        if ($pending !== null && !$this->isExpired($pending->expires_at)) {
            Response::conflict('Username already taken');
            exit;
        }

        if ($pending !== null) {
            $this->pendingRepository->deleteById($pending->id);
        }
    }

    private function assertEmailAvailable(string $email): void
    {
        if ($this->userRepository->findByEmail($email) !== null) {
            Response::conflict('Email already registered');
            exit;
        }

        $pending = $this->pendingRepository->findByEmail($email);
        if ($pending !== null && !$this->isExpired($pending->expires_at)) {
            Response::conflict('Email already registered');
            exit;
        }

        if ($pending !== null) {
            $this->pendingRepository->deleteById($pending->id);
        }
    }

    private function isExpired(string $expiresAt): bool
    {
        $expiresTs = strtotime($expiresAt);

        return $expiresTs === false || time() >= $expiresTs;
    }

    private function generateToken(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
