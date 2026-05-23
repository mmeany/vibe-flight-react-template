<?php

declare(strict_types=1);

namespace App\Utils;

use App\Config\AppConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtUtil
{
    public static function generateToken(int $userId, string $username): string
    {
        $payload = [
            'sub' => $userId,
            'username' => $username,
            'iat' => time(),
            'exp' => time() + (AppConfig::getJwtExpirationDays() * 86400),
        ];

        return JWT::encode($payload, AppConfig::getJwtSecret(), 'HS256');
    }

    public static function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(AppConfig::getJwtSecret(), 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
