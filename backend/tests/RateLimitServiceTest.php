<?php

declare(strict_types=1);

namespace App\Tests;

use App\Database\Database;
use App\Repositories\RateLimitRepository;
use App\Services\RateLimitService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RateLimitServiceTest extends TestCase
{
    public function testEnforceAllowsFirstSubmission(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('commit');

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = $this->createMock(RateLimitRepository::class);
        $repo->method('getForUpdate')->willReturn(['count' => 0, 'window_start' => null]);
        $repo->expects($this->atLeastOnce())->method('upsert');

        $logger = $this->createMock(LoggerInterface::class);
        $service = new RateLimitService($database, $repo, $logger);

        $service->enforce('user@example.com', '127.0.0.1');
        $this->addToAssertionCount(1);
    }

    public function testEnforceBlocksSecondMinuteSubmission(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('rollBack');

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = $this->createMock(RateLimitRepository::class);
        $repo->method('getForUpdate')->willReturnCallback(function (string $keyType, string $keyValue, string $windowType) {
            if ($keyType === 'email' && $windowType === 'minute') {
                return ['count' => 1, 'window_start' => gmdate('Y-m-d H:i:s')];
            }

            return ['count' => 0, 'window_start' => null];
        });

        $logger = $this->createMock(LoggerInterface::class);
        $service = new RateLimitService($database, $repo, $logger);

        $this->expectException(\RuntimeException::class);
        $service->enforce('user@example.com', '127.0.0.1');
    }
}
