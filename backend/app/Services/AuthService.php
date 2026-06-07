<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use App\Http\Response;
use App\Repositories\UserRepository;
use App\Utils\JwtUtil;
use App\Utils\UserValidation;
use Psr\Log\LoggerInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function login(string $username, string $password): array
    {
        $user = $this->userRepository->findByUsername($username);

        if ($user === null || !password_verify($password, $user->getPasswordHash())) {
            Response::unauthorized('Invalid username or password');
            exit;
        }

        $token = JwtUtil::generateToken($user->id, $user->username);

        $userData = $user->toArray();
        $userData['is_admin'] = AppConfig::isAdminUsername($user->username);

        return [
            'token' => $token,
            'user' => $userData,
        ];
    }

    public function getMe(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            Response::notFound('User not found');
            exit;
        }

        $data = $user->toArray();
        $data['is_admin'] = AppConfig::isAdminUsername($user->username);

        return $data;
    }

    public function updateSettings(int $userId, array $partial): array
    {
        $allowed = ['theme_mode', 'date_format', 'user_alias', 'timezone'];
        $filtered = [];

        foreach ($partial as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $filtered[$key] = trim((string) $value);
            }
        }

        if ($filtered === []) {
            Response::validationError('No valid settings keys provided');
            exit;
        }

        if (isset($filtered['user_alias'])) {
            $alias = $filtered['user_alias'];
            if ($alias === '' || !preg_match('/^[a-zA-Z0-9]+$/', $alias)) {
                Response::validationError('User alias must be alphanumeric');
                exit;
            }
            if (mb_strlen($alias) > 40) {
                Response::validationError('User alias must be 40 characters or fewer');
                exit;
            }
        }

        if (isset($filtered['theme_mode']) && !in_array($filtered['theme_mode'], ['light', 'dark'], true)) {
            Response::validationError('Theme mode must be light or dark');
            exit;
        }

        if (isset($filtered['date_format']) && !in_array($filtered['date_format'], ['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD'], true)) {
            Response::validationError('Invalid date format');
            exit;
        }

        if (isset($filtered['timezone'])) {
            try {
                new \DateTimeZone($filtered['timezone']);
            } catch (\Exception) {
                Response::validationError('Invalid timezone');
                exit;
            }
        }

        $settings = $this->userRepository->updateSettings($userId, $filtered);

        $this->logger->info('Settings updated', ['user_id' => $userId, 'changes' => $filtered]);

        return $settings;
    }

    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword,
        string $passwordReminder,
    ): void {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            Response::notFound('User not found');
            exit;
        }

        if (!password_verify($currentPassword, $user->getPasswordHash())) {
            Response::unauthorized('Current password is incorrect');
            exit;
        }

        $errors = array_merge(
            UserValidation::validatePassword($newPassword),
            UserValidation::validatePasswordReminder($passwordReminder),
        );
        if ($errors !== []) {
            Response::validationError(implode(' ', $errors));
            exit;
        }

        if (password_verify($newPassword, $user->getPasswordHash())) {
            Response::validationError('New password must be different from current password');
            exit;
        }

        $passwordHash = UserValidation::hashPassword($newPassword);
        $this->userRepository->updatePassword($userId, $passwordHash, trim($passwordReminder));

        $this->logger->info('Password changed', ['user_id' => $userId]);
    }

}
