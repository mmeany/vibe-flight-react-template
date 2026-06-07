<?php

declare(strict_types=1);

namespace App\Utils;

class VerificationCodeUtil
{
    public static function generate(): string
    {
        return (string) random_int(100000, 999999);
    }

    public static function hash(string $code): string
    {
        return password_hash($code, PASSWORD_BCRYPT);
    }

    public static function verify(string $code, string $codeHash): bool
    {
        return password_verify($code, $codeHash);
    }
}
