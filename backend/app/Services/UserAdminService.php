<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Response;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Utils\UserValidation;
use Psr\Log\LoggerInterface;

class UserAdminService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function createUser(string $username, string $email, string $password, ?string $userAlias = null): User
    {
        $username = trim($username);
        $email = trim($email);
        $alias = trim($userAlias ?? '') !== '' ? trim($userAlias) : $username;

        $errors = UserValidation::validateRegistration($username, $email, $password);
        if ($errors !== []) {
            Response::validationError(implode(' ', $errors));
            exit;
        }

        $aliasError = UserValidation::validateUserAlias($alias);
        if ($aliasError !== null) {
            Response::validationError($aliasError);
            exit;
        }

        if ($this->userRepository->findByUsername($username) !== null) {
            Response::conflict('Username already taken');
            exit;
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            Response::conflict('Email already registered');
            exit;
        }

        $passwordHash = UserValidation::hashPassword($password);
        $settings = [
            'theme_mode' => 'light',
            'date_format' => 'MM/DD/YYYY',
            'user_alias' => $alias,
        ];

        $user = $this->userRepository->create($username, $email, $passwordHash, $settings);
        $this->logger->info('Admin created user', ['user_id' => $user->id, 'username' => $username]);

        return $user;
    }

    /**
     * @return array{summary: array{created: int, failed: int}, rows: list<array<string, mixed>>}
     */
    public function importCsv(string $csvContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $csvContent) ?: [];
        if ($lines === []) {
            Response::validationError('CSV file is empty');
            exit;
        }

        $headerLine = array_shift($lines);
        if ($headerLine === null || trim($headerLine) === '') {
            Response::validationError('CSV header row is required');
            exit;
        }

        $headers = array_map(
            static fn (string $col): string => strtolower(trim($col)),
            str_getcsv($headerLine)
        );

        $required = ['username', 'email', 'password'];
        foreach ($required as $col) {
            if (!in_array($col, $headers, true)) {
                Response::validationError("CSV must include column: $col");
                exit;
            }
        }

        $rows = [];
        $created = 0;
        $failed = 0;
        $lineNumber = 1;

        foreach ($lines as $line) {
            $lineNumber++;
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line);
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = trim($values[$index] ?? '');
            }

            $username = $row['username'] ?? '';
            $email = $row['email'] ?? '';
            $password = $row['password'] ?? '';
            $userAlias = $row['user_alias'] ?? '';

            try {
                $user = $this->createUserWithoutExit($username, $email, $password, $userAlias !== '' ? $userAlias : null);
                $created++;
                $rows[] = [
                    'line' => $lineNumber,
                    'username' => $username,
                    'status' => 'created',
                    'id' => $user->id,
                ];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [
                    'line' => $lineNumber,
                    'username' => $username,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $this->logger->info('Admin CSV import completed', ['created' => $created, 'failed' => $failed]);

        return [
            'summary' => ['created' => $created, 'failed' => $failed],
            'rows' => $rows,
        ];
    }

    /**
     * @return User[]
     */
    public function listUsers(bool $includeInactive): array
    {
        return $this->userRepository->findAll($includeInactive);
    }

    public function getUser(int $id): User
    {
        $user = $this->userRepository->findById($id);
        if ($user === null) {
            Response::notFound('User not found');
            exit;
        }

        return $user;
    }

    public function updateUser(
        int $id,
        string $username,
        string $email,
        ?string $password,
        ?string $userAlias,
        int $actingUserId,
    ): User {
        $user = $this->getUser($id);

        if (!$user->isActive()) {
            Response::validationError('Cannot edit an inactive user; restore first');
            exit;
        }

        $username = trim($username);
        $email = trim($email);

        $errors = UserValidation::validateUsernameEmail($username, $email);
        if ($password !== null && $password !== '') {
            $errors = array_merge($errors, UserValidation::validatePassword($password));
        }
        if ($errors !== []) {
            Response::validationError(implode(' ', $errors));
            exit;
        }

        $settings = $user->getSettings() ?? [];
        if ($userAlias !== null) {
            $alias = trim($userAlias);
            if ($alias === '') {
                $alias = $username;
            }
            $aliasError = UserValidation::validateUserAlias($alias);
            if ($aliasError !== null) {
                Response::validationError($aliasError);
                exit;
            }
            $settings['user_alias'] = $alias;
        }

        if ($this->userRepository->findByUsernameExcludingId($username, $id) !== null) {
            Response::conflict('Username already taken');
            exit;
        }

        if ($this->userRepository->findByEmailExcludingId($email, $id) !== null) {
            Response::conflict('Email already registered');
            exit;
        }

        $passwordHash = ($password !== null && $password !== '')
            ? UserValidation::hashPassword($password)
            : $user->getPasswordHash();

        $updated = $this->userRepository->update($id, $username, $email, $passwordHash, $settings);
        $this->logger->info('Admin updated user', ['user_id' => $id, 'acting_user_id' => $actingUserId]);

        return $updated;
    }

    public function deactivate(int $id, int $actingUserId): void
    {
        if ($id === $actingUserId) {
            Response::validationError('Cannot deactivate your own account');
            exit;
        }

        $user = $this->getUser($id);

        if (!$user->isActive()) {
            Response::validationError('User is already inactive');
            exit;
        }

        $this->userRepository->softDelete($id);
        $this->logger->info('Admin deactivated user', ['user_id' => $id, 'acting_user_id' => $actingUserId]);
    }

    public function restore(int $id, int $actingUserId): User
    {
        $user = $this->getUser($id);

        if ($user->isActive()) {
            Response::validationError('User is already active');
            exit;
        }

        $this->userRepository->restore($id);
        $this->logger->info('Admin restored user', ['user_id' => $id, 'acting_user_id' => $actingUserId]);

        return $this->getUser($id);
    }

    private function createUserWithoutExit(
        string $username,
        string $email,
        string $password,
        ?string $userAlias = null,
    ): User {
        $username = trim($username);
        $email = trim($email);
        $alias = trim($userAlias ?? '') !== '' ? trim($userAlias) : $username;

        $errors = UserValidation::validateRegistration($username, $email, $password);
        if ($errors !== []) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        $aliasError = UserValidation::validateUserAlias($alias);
        if ($aliasError !== null) {
            throw new \InvalidArgumentException($aliasError);
        }

        if ($this->userRepository->findByUsername($username) !== null) {
            throw new \InvalidArgumentException('Username already taken');
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new \InvalidArgumentException('Email already registered');
        }

        $passwordHash = UserValidation::hashPassword($password);
        $settings = [
            'theme_mode' => 'light',
            'date_format' => 'MM/DD/YYYY',
            'user_alias' => $alias,
        ];

        return $this->userRepository->create($username, $email, $passwordHash, $settings);
    }
}
