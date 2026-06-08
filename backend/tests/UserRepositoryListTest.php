<?php

declare(strict_types=1);

namespace App\Tests;

use App\Database\Database;
use App\DTOs\UserListQuery;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class UserRepositoryListTest extends TestCase
{
    public function testFindPaginatedAppliesInactiveAndSearchFilters(): void
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

        $repo = new UserRepository($database);
        $query = new UserListQuery(
            includeInactive: false,
            page: 1,
            perPage: 25,
            search: 'alice',
            sort: 'username',
            order: 'asc',
        );

        $repo->findPaginated($query);

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('WHERE deleted_at IS NULL', $capturedSql);
        $this->assertStringContainsString('username LIKE :search', $capturedSql);
        $this->assertStringContainsString('email LIKE :search', $capturedSql);
        $this->assertStringContainsString("JSON_EXTRACT(settings, '$.user_alias')", $capturedSql);
        $this->assertStringContainsString('ORDER BY username ASC, id ASC', $capturedSql);
        $this->assertStringContainsString('LIMIT :limit OFFSET :offset', $capturedSql);
        $this->assertSame('%alice%', $boundParams[':search']);
        $this->assertSame(25, $boundParams[':limit']);
        $this->assertSame(0, $boundParams[':offset']);
    }

    public function testFindPaginatedAppliesEmailSortAndPaginationOffset(): void
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

        $repo = new UserRepository($database);
        $query = new UserListQuery(
            includeInactive: true,
            page: 2,
            perPage: 10,
            search: '',
            sort: 'email',
            order: 'desc',
        );

        $repo->findPaginated($query);

        $this->assertNotNull($capturedSql);
        $this->assertStringNotContainsString('deleted_at IS NULL', $capturedSql);
        $this->assertStringContainsString('ORDER BY email DESC, id ASC', $capturedSql);
        $this->assertSame(10, $boundParams[':limit']);
        $this->assertSame(10, $boundParams[':offset']);
    }

    public function testFindPaginatedAppliesAliasSort(): void
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

        $repo = new UserRepository($database);
        $query = new UserListQuery(
            includeInactive: false,
            page: 1,
            perPage: 25,
            search: '',
            sort: 'user_alias',
            order: 'asc',
        );

        $repo->findPaginated($query);

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString("JSON_EXTRACT(settings, '$.user_alias')", $capturedSql);
        $this->assertStringContainsString('ORDER BY JSON_UNQUOTE(JSON_EXTRACT(settings', $capturedSql);
    }

    public function testCountPaginatedUsesSameWhereClauseAsFindPaginated(): void
    {
        $capturedSql = null;
        $boundParams = [];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue')->willReturnCallback(function ($key, $value) use (&$boundParams): bool {
            $boundParams[$key] = $value;

            return true;
        });
        $stmt->method('execute');
        $stmt->method('fetchColumn')->willReturn('5');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($stmt, &$capturedSql): PDOStatement {
            $capturedSql = $sql;

            return $stmt;
        });

        $database = $this->createMock(Database::class);
        $database->method('getPdo')->willReturn($pdo);

        $repo = new UserRepository($database);
        $query = new UserListQuery(
            includeInactive: false,
            page: 1,
            perPage: 25,
            search: 'bob',
            sort: 'username',
            order: 'asc',
        );

        $total = $repo->countPaginated($query);

        $this->assertSame(5, $total);
        $this->assertNotNull($capturedSql);
        $this->assertStringStartsWith('SELECT COUNT(*) FROM users', $capturedSql);
        $this->assertStringContainsString('WHERE deleted_at IS NULL', $capturedSql);
        $this->assertStringContainsString('username LIKE :search', $capturedSql);
        $this->assertStringNotContainsString('ORDER BY', $capturedSql);
        $this->assertSame('%bob%', $boundParams[':search']);
    }

    public function testUserListQueryIgnoresShortSearchTermsAndInvalidSort(): void
    {
        $query = UserListQuery::fromRequestParams([
            'search' => 'a',
            'sort' => 'invalid',
            'order' => 'sideways',
            'page' => 0,
            'per_page' => 200,
        ]);

        $this->assertSame('', $query->search);
        $this->assertSame('username', $query->sort);
        $this->assertSame('asc', $query->order);
        $this->assertSame(1, $query->page);
        $this->assertSame(100, $query->perPage);
    }
}
