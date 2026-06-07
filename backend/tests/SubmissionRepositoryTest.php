<?php

declare(strict_types=1);

namespace App\Tests;

use App\Database\Database;
use App\DTOs\SubmissionListQuery;
use App\Repositories\SubmissionRepository;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class SubmissionRepositoryTest extends TestCase
{
    public function testListSubmissionsAppliesIgnoredAndSearchFilters(): void
    {
        $capturedSql = null;
        $boundParams = [];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue')->willReturnCallback(function ($key, $value) use (&$boundParams): bool {
            $boundParams[$key] = $value;

            return true;
        });
        $stmt->method('execute');
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($stmt, &$capturedSql): PDOStatement {
            $capturedSql = $sql;

            return $stmt;
        });

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = new SubmissionRepository($database);
        $query = new SubmissionListQuery(
            includeIgnored: false,
            page: 1,
            perPage: 25,
            search: 'alice',
            sort: 'created_at',
            order: 'desc',
            status: 'all',
        );

        $repo->listSubmissions($query);

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('WHERE ignored = 0', $capturedSql);
        $this->assertStringContainsString('email LIKE :search', $capturedSql);
        $this->assertStringContainsString("JSON_EXTRACT(payload, '$.question')", $capturedSql);
        $this->assertStringContainsString("JSON_EXTRACT(payload, '$.known_as')", $capturedSql);
        $this->assertStringContainsString('ORDER BY created_at DESC, id DESC', $capturedSql);
        $this->assertStringContainsString('LIMIT :limit OFFSET :offset', $capturedSql);
        $this->assertSame('%alice%', $boundParams[':search']);
        $this->assertSame(25, $boundParams[':limit']);
        $this->assertSame(0, $boundParams[':offset']);
    }

    public function testListSubmissionsAppliesNewStatusFilter(): void
    {
        $capturedSql = null;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue')->willReturn(true);
        $stmt->method('execute');
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($stmt, &$capturedSql): PDOStatement {
            $capturedSql = $sql;

            return $stmt;
        });

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = new SubmissionRepository($database);
        $query = new SubmissionListQuery(
            includeIgnored: true,
            page: 2,
            perPage: 10,
            search: '',
            sort: 'email',
            order: 'asc',
            status: 'new',
        );

        $repo->listSubmissions($query);

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('ignored = 0', $capturedSql);
        $this->assertStringContainsString('follow_up_sent_at IS NULL', $capturedSql);
        $this->assertStringContainsString('ORDER BY email ASC, id DESC', $capturedSql);
    }

    public function testListSubmissionsAppliesRepliedStatusFilter(): void
    {
        $capturedSql = null;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue')->willReturn(true);
        $stmt->method('execute');
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($stmt, &$capturedSql): PDOStatement {
            $capturedSql = $sql;

            return $stmt;
        });

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = new SubmissionRepository($database);
        $query = new SubmissionListQuery(
            includeIgnored: false,
            page: 1,
            perPage: 25,
            search: '',
            sort: 'status',
            order: 'desc',
            status: 'replied',
        );

        $repo->listSubmissions($query);

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('follow_up_sent_at IS NOT NULL', $capturedSql);
        $this->assertStringContainsString('CASE WHEN ignored = 1 THEN 2', $capturedSql);
        $this->assertStringContainsString('ORDER BY CASE WHEN ignored = 1 THEN 2', $capturedSql);
    }

    public function testCountSubmissionsUsesSameWhereClauseAsList(): void
    {
        $capturedSql = null;
        $boundParams = [];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue')->willReturnCallback(function ($key, $value) use (&$boundParams): bool {
            $boundParams[$key] = $value;

            return true;
        });
        $stmt->method('execute');
        $stmt->method('fetchColumn')->willReturn('3');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($stmt, &$capturedSql): PDOStatement {
            $capturedSql = $sql;

            return $stmt;
        });

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = new SubmissionRepository($database);
        $query = new SubmissionListQuery(
            includeIgnored: false,
            page: 1,
            perPage: 25,
            search: 'help',
            sort: 'created_at',
            order: 'desc',
            status: 'all',
        );

        $total = $repo->countSubmissions($query);

        $this->assertSame(3, $total);
        $this->assertNotNull($capturedSql);
        $this->assertStringStartsWith('SELECT COUNT(*) FROM submissions', $capturedSql);
        $this->assertStringContainsString('WHERE ignored = 0', $capturedSql);
        $this->assertStringContainsString('email LIKE :search', $capturedSql);
        $this->assertStringNotContainsString('ORDER BY', $capturedSql);
        $this->assertSame('%help%', $boundParams[':search']);
    }

    public function testSubmissionListQueryIgnoresShortSearchTerms(): void
    {
        $query = SubmissionListQuery::fromRequestParams([
            'search' => 'a',
            'sort' => 'invalid',
            'order' => 'sideways',
            'status' => 'unknown',
        ]);

        $this->assertSame('', $query->search);
        $this->assertSame('created_at', $query->sort);
        $this->assertSame('desc', $query->order);
        $this->assertSame('all', $query->status);
    }
}
