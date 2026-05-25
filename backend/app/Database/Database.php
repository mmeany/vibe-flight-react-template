<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\AppConfig;
use PDO;

class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            AppConfig::getDbHost(),
            AppConfig::getDbPort(),
            AppConfig::getDbDatabase()
        );

        $this->pdo = new PDO($dsn, AppConfig::getDbUsername(), AppConfig::getDbPassword(), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function migrate(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                password_reminder VARCHAR(255) NOT NULL DEFAULT \'No hint\',
                settings JSON NOT NULL DEFAULT (\'{}\'),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        try {
            $this->pdo->exec("ALTER TABLE users ADD COLUMN settings JSON NOT NULL DEFAULT ('{}')");
        } catch (\PDOException $e) {
            if ($e->getCode() !== '42S21') {
                throw $e;
            }
        }

        try {
            $this->pdo->exec('ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL');
        } catch (\PDOException $e) {
            if ($e->getCode() !== '42S21') {
                throw $e;
            }
        }

        try {
            $this->pdo->exec(
                "ALTER TABLE users ADD COLUMN password_reminder VARCHAR(255) NOT NULL DEFAULT 'No hint'"
            );
        } catch (\PDOException $e) {
            if ($e->getCode() !== '42S21') {
                throw $e;
            }
        }
    }
}
