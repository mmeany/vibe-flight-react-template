<?php

declare(strict_types=1);

namespace App\Utils;

class UserValidation
{
    public static function validateUsernameEmail(string $username, string $email): array
    {
        $errors = [];

        if (trim($username) === '') {
            $errors[] = 'Username is required.';
        }
        if (trim($email) === '') {
            $errors[] = 'Email is required.';
        }

        return $errors;
    }

    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (mb_strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one digit.';
        }

        return $errors;
    }

    public static function validatePasswordReminder(string $passwordReminder): array
    {
        $errors = [];
        $trimmed = trim($passwordReminder);

        if ($trimmed === '') {
            $errors[] = 'Password reminder is required.';
        }
        if (mb_strlen($trimmed) > 255) {
            $errors[] = 'Password reminder must be 255 characters or fewer.';
        }

        return $errors;
    }

    public static function validateRegistration(
        string $username,
        string $email,
        string $password,
        string $passwordReminder,
    ): array {
        return array_merge(
            self::validateUsernameEmail($username, $email),
            self::validatePassword($password),
            self::validatePasswordReminder($passwordReminder),
        );
    }

    public static function validateUserAlias(string $alias): ?string
    {
        if ($alias === '') {
            return 'User alias must not be empty';
        }
        if (!preg_match('/^[a-zA-Z0-9]+$/', $alias)) {
            return 'User alias must be alphanumeric';
        }
        if (mb_strlen($alias) > 40) {
            return 'User alias must be 40 characters or fewer';
        }

        return null;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
