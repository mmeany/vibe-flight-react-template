<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use App\Repositories\RateLimitRepository;
use Psr\Log\LoggerInterface;

class RateLimitService
{
    /** @var array<string, int> */
    private const EMAIL_LIMITS = ['minute' => 1, 'hour' => 3, 'lifetime' => 10];

    private const IP_HOUR_LIMIT = 5;

    public function __construct(
        private readonly Database $database,
        private readonly RateLimitRepository $rateLimitRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function enforce(string $email, string $ip): void
    {
        $pdo = $this->database->getPdo();
        $pdo->beginTransaction();
        try {
            $this->checkEmailLimits(strtolower($email));
            $this->checkIpLimit($ip);
            $this->incrementEmailLimits(strtolower($email));
            $this->incrementIpLimit($ip);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function checkEmailLimits(string $email): void
    {
        foreach (self::EMAIL_LIMITS as $windowType => $limit) {
            $this->assertUnderLimit('email', $email, $windowType, $limit);
        }
    }

    private function checkIpLimit(string $ip): void
    {
        $this->assertUnderLimit('ip', $ip, 'hour', self::IP_HOUR_LIMIT);
    }

    private function assertUnderLimit(string $keyType, string $keyValue, string $windowType, int $limit): void
    {
        $row = $this->rateLimitRepository->getForUpdate($keyType, $keyValue, $windowType);
        $count = $row['count'];
        $windowStart = $row['window_start'];

        if ($windowType === 'lifetime') {
            if ($count >= $limit) {
                $this->logRateLimit($keyType, $windowType);
                throw new \RuntimeException('You have reached the maximum number of submissions. Please try again later.');
            }

            return;
        }

        $windowSeconds = $windowType === 'minute' ? 60 : 3600;
        $now = time();

        if ($windowStart === null) {
            return;
        }

        $startTs = strtotime($windowStart);
        if ($startTs === false || ($now - $startTs) >= $windowSeconds) {
            return;
        }

        if ($count >= $limit) {
            $this->logRateLimit($keyType, $windowType);
            throw new \RuntimeException('Too many submissions. Please wait before trying again.');
        }
    }

    private function incrementEmailLimits(string $email): void
    {
        foreach (array_keys(self::EMAIL_LIMITS) as $windowType) {
            $this->increment('email', $email, $windowType);
        }
    }

    private function incrementIpLimit(string $ip): void
    {
        $this->increment('ip', $ip, 'hour');
    }

    private function increment(string $keyType, string $keyValue, string $windowType): void
    {
        $row = $this->rateLimitRepository->getForUpdate($keyType, $keyValue, $windowType);
        $count = $row['count'];
        $windowStart = $row['window_start'];
        $now = time();

        if ($windowType === 'lifetime') {
            $this->rateLimitRepository->upsert(
                $keyType,
                $keyValue,
                $windowType,
                $count + 1,
                $windowStart ?? gmdate('Y-m-d H:i:s', $now),
            );

            return;
        }

        $windowSeconds = $windowType === 'minute' ? 60 : 3600;
        $startTs = $windowStart !== null ? strtotime($windowStart) : false;
        $windowExpired = $startTs === false || ($now - $startTs) >= $windowSeconds;

        if ($windowExpired) {
            $this->rateLimitRepository->upsert($keyType, $keyValue, $windowType, 1, gmdate('Y-m-d H:i:s', $now));

            return;
        }

        $this->rateLimitRepository->upsert($keyType, $keyValue, $windowType, $count + 1, $windowStart);
    }

    private function logRateLimit(string $keyType, string $windowType): void
    {
        $this->logger->info('rate_limit', [
            'event' => 'rate_limit',
            'key_type' => $keyType,
            'window_type' => $windowType,
        ]);
    }
}
