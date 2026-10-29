<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * Runs numbered migrations from migrations/NNN_description.php.
 *
 * Each migration file returns a callable taking a PDO. Every migration runs in
 * its own transaction; a backup of the database is taken before migrating.
 */
final class Migrator
{
    public function __construct(
        private PDO $pdo,
        private string $dir,
        private ?Backup $backup = null,
    ) {
    }

    /** @return array<string, string> version => file path, sorted */
    public function available(): array
    {
        $result = [];
        foreach (glob($this->dir . '/*.php') ?: [] as $file) {
            if (preg_match('/^(\d+)_[a-z0-9_]+\.php$/', basename($file), $m)) {
                $result[basename($file, '.php')] = $file;
            }
        }
        ksort($result, SORT_NATURAL);
        return $result;
    }

    /** @return list<string> */
    public function applied(): array
    {
        $this->ensureTable();
        return $this->pdo->query('SELECT version FROM schema_migrations ORDER BY version')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return list<string> Versions not yet applied */
    public function pending(): array
    {
        return array_values(array_diff(array_keys($this->available()), $this->applied()));
    }

    /**
     * @return array{applied: list<string>, backup: ?string}
     */
    public function migrate(): array
    {
        $pending = $this->pending();
        if ($pending === []) {
            return ['applied' => [], 'backup' => null];
        }

        $backupFile = null;
        if ($this->backup !== null && $this->applied() !== []) {
            $backupFile = $this->backup->create('pre-migrate');
        }

        $files = $this->available();
        $done = [];
        foreach ($pending as $version) {
            $migration = require $files[$version];
            if (!is_callable($migration)) {
                throw new \RuntimeException("Migration $version must return a callable.");
            }
            $this->pdo->beginTransaction();
            try {
                $migration($this->pdo);
                $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)');
                $stmt->execute([$version, gmdate('Y-m-d H:i:s')]);
                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                throw new \RuntimeException("Migration $version failed: " . $e->getMessage(), 0, $e);
            }
            $done[] = $version;
        }
        return ['applied' => $done, 'backup' => $backupFile];
    }

    private function ensureTable(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            version TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL
        )');
    }
}
