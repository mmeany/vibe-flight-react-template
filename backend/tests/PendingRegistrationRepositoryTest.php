<?php

declare(strict_types=1);

namespace App\Tests;

use App\Database\Database;
use App\Repositories\PendingRegistrationRepository;
use PHPUnit\Framework\TestCase;

class PendingRegistrationRepositoryTest extends TestCase
{
    public function testFindByUsernameReturnsNullWhenNoRow(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = new PendingRegistrationRepository($database);

        $this->assertNull($repo->findByUsername('nobody'));
    }

    public function testDeleteExpiredExecutesCleanupQuery(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())
            ->method('exec')
            ->with($this->stringContains('DELETE FROM pending_registrations'));

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = new PendingRegistrationRepository($database);
        $repo->deleteExpired();
    }
}
