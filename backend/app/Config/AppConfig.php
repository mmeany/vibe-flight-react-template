<?php

declare(strict_types=1);

namespace App\Config;

use Dotenv\Dotenv;

class AppConfig
{
    private static bool $loaded = false;
    private static string $dbHost;
    private static string $dbPort;
    private static string $dbUsername;
    private static string $dbPassword;
    private static string $dbDatabase;
    private static string $jwtSecret;
    private static int $jwtExpirationDays;
    private static bool $registrationEnabled;
    /** @var string[] */
    private static array $adminUsernames;
    private static string $logDir;
    private static string $logLevel;
    private static string $appEnv;
    private static string $challengeSecret;
    private static string $smtpHost;
    private static int $smtpPort;
    private static string $smtpSecure;
    private static string $smtpUser;
    private static string $smtpPass;
    private static string $smtpFromEmail;
    private static string $smtpFromName;
    private static ?bool $smtpAuth;
    /** @var string[] */
    private static array $corsOrigins;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $repoRoot = realpath(__DIR__ . '/../../..') ?: (__DIR__ . '/../../..');
        $backendRoot = realpath(__DIR__ . '/../..') ?: (__DIR__ . '/../..');

        if (is_file($repoRoot . '/.env')) {
            $dotenv = Dotenv::createImmutable($repoRoot);
            $dotenv->load();
        } elseif (is_file($backendRoot . '/.env')) {
            $dotenv = Dotenv::createImmutable($backendRoot);
            $dotenv->load();
        }

        self::$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
        self::$dbPort = $_ENV['DB_PORT'] ?? '3306';
        self::$dbUsername = $_ENV['DB_USERNAME'] ?? 'mark';
        self::$dbPassword = $_ENV['DB_PASSWORD'] ?? 'Password123';
        self::$dbDatabase = $_ENV['DB_DATABASE'] ?? 'flight_react_app';
        self::$jwtSecret = $_ENV['JWT_SECRET'] ?? '';
        self::$jwtExpirationDays = (int) ($_ENV['JWT_EXPIRATION_DAYS'] ?? 30);
        self::$registrationEnabled = filter_var($_ENV['REGISTRATION_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $adminRaw = $_ENV['ADMIN_USERNAMES'] ?? '';
        self::$adminUsernames = array_values(array_filter(array_map(
            static fn (string $name): string => trim($name),
            explode(',', $adminRaw)
        ), static fn (string $name): bool => $name !== ''));
        self::$logDir = $_ENV['LOG_DIR'] ?? 'logs';
        self::$logLevel = strtoupper($_ENV['LOG_LEVEL'] ?? 'DEBUG');
        self::$appEnv = strtolower($_ENV['APP_ENV'] ?? 'development');
        self::$challengeSecret = $_ENV['CHALLENGE_SECRET'] ?? '';
        self::$smtpHost = $_ENV['SMTP_HOST'] ?? '127.0.0.1';
        self::$smtpPort = (int) ($_ENV['SMTP_PORT'] ?? 1025);
        self::$smtpSecure = strtolower($_ENV['SMTP_SECURE'] ?? 'none');
        self::$smtpUser = $_ENV['SMTP_USER'] ?? '';
        self::$smtpPass = $_ENV['SMTP_PASS'] ?? '';
        self::$smtpFromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@example.com';
        self::$smtpFromName = $_ENV['SMTP_FROM_NAME'] ?? 'Flight React App';
        $smtpAuthRaw = $_ENV['SMTP_AUTH'] ?? null;
        self::$smtpAuth = $smtpAuthRaw === null || $smtpAuthRaw === ''
            ? null
            : filter_var($smtpAuthRaw, FILTER_VALIDATE_BOOLEAN);
        $corsRaw = $_ENV['CORS_ORIGINS'] ?? 'http://localhost:5173';
        self::$corsOrigins = array_values(array_filter(array_map(
            static fn (string $origin): string => trim($origin),
            explode(',', $corsRaw)
        ), static fn (string $origin): bool => $origin !== ''));

        self::$loaded = true;
    }

    public static function getDbHost(): string
    {
        return self::$dbHost;
    }

    public static function getDbPort(): string
    {
        return self::$dbPort;
    }

    public static function getDbUsername(): string
    {
        return self::$dbUsername;
    }

    public static function getDbPassword(): string
    {
        return self::$dbPassword;
    }

    public static function getDbDatabase(): string
    {
        return self::$dbDatabase;
    }

    public static function getJwtSecret(): string
    {
        return self::$jwtSecret;
    }

    public static function getJwtExpirationDays(): int
    {
        return self::$jwtExpirationDays;
    }

    public static function isRegistrationEnabled(): bool
    {
        return self::$registrationEnabled;
    }

    /**
     * @return string[]
     */
    public static function getAdminUsernames(): array
    {
        return self::$adminUsernames;
    }

    public static function isAdminUsername(string $username): bool
    {
        return in_array($username, self::$adminUsernames, true);
    }

    public static function getLogDir(): string
    {
        return self::$logDir;
    }

    public static function getLogLevel(): string
    {
        return self::$logLevel;
    }

    public static function getAppEnv(): string
    {
        return self::$appEnv;
    }

    public static function isProduction(): bool
    {
        return self::$appEnv === 'production';
    }

    public static function getChallengeSecret(): string
    {
        return self::$challengeSecret;
    }

    public static function getSmtpHost(): string
    {
        return self::$smtpHost;
    }

    public static function getSmtpPort(): int
    {
        return self::$smtpPort;
    }

    public static function getSmtpSecure(): string
    {
        return self::$smtpSecure;
    }

    public static function getSmtpUser(): string
    {
        return self::$smtpUser;
    }

    public static function getSmtpPass(): string
    {
        return self::$smtpPass;
    }

    public static function getSmtpFromEmail(): string
    {
        return self::$smtpFromEmail;
    }

    public static function getSmtpFromName(): string
    {
        return self::$smtpFromName;
    }

    public static function isSmtpAuth(): bool
    {
        if (self::$smtpAuth !== null) {
            return self::$smtpAuth;
        }

        return self::$smtpUser !== '';
    }

    /**
     * @return string[]
     */
    public static function getCorsOrigins(): array
    {
        return self::$corsOrigins;
    }

    public static function resolveLogLevel(): int
    {
        return match (self::$logLevel) {
            'DEBUG' => 100,
            'INFO' => 200,
            'NOTICE' => 250,
            'WARNING' => 300,
            'ERROR' => 400,
            'CRITICAL' => 500,
            'ALERT' => 550,
            'EMERGENCY' => 600,
            default => 100,
        };
    }
}
