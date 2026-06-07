<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use Psr\Log\LoggerInterface;

class MigrationRunner
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LoggerInterface $logger,
        private readonly string $migrationsPath,
    ) {}

    public function run(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        if (!is_dir($this->migrationsPath)) {
            return;
        }

        $files = glob($this->migrationsPath . '/*.sql') ?: [];
        sort($files);

        $applied = $this->getAppliedMigrations();

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                throw new \RuntimeException("Migration file is empty: $name");
            }

            $this->pdo->exec($sql);

            $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
            $stmt->execute([':migration' => $name]);

            $this->logger->info('Migration applied', ['migration' => $name]);
        }
    }

    /**
     * @return string[]
     */
    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT migration FROM schema_migrations ORDER BY id');
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('strval', $rows);
    }
}
