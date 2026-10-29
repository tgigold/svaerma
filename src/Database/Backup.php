<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * Consistent SQLite snapshots via VACUUM INTO (works while the DB is open).
 */
final class Backup
{
    public function __construct(
        private PDO $pdo,
        private string $dir,
        private int $keep = 20,
    ) {
    }

    /**
     * @return string Path of the created backup file
     */
    public function create(string $label = 'manual'): string
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0750, true);
        }
        $label = preg_replace('/[^a-z0-9-]+/i', '-', $label) ?? 'backup';
        $file = sprintf('%s/app-%s-%s.sqlite', $this->dir, gmdate('Ymd-His'), $label);
        $stmt = $this->pdo->prepare('VACUUM INTO ?');
        $stmt->execute([$file]);
        $this->prune();
        return $file;
    }

    /** @return list<string> Backup files, newest first */
    public function list(): array
    {
        $files = glob($this->dir . '/app-*.sqlite') ?: [];
        rsort($files);
        return $files;
    }

    private function prune(): void
    {
        foreach (array_slice($this->list(), $this->keep) as $old) {
            @unlink($old);
        }
    }
}
