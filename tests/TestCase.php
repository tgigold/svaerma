<?php

declare(strict_types=1);

namespace App\Tests;

use App\Database\Connection;
use App\Database\Migrator;
use PDO;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tempDir(): string
    {
        $dir = sys_get_temp_dir() . '/checkin-test-' . bin2hex(random_bytes(6));
        mkdir($dir, 0700, true);
        $this->tempDirs[] = $dir;
        return $dir;
    }

    /** Fresh in-memory database with all real migrations applied. */
    protected function migratedPdo(): PDO
    {
        $pdo = Connection::open(':memory:');
        (new Migrator($pdo, APP_ROOT . '/migrations'))->migrate();
        return $pdo;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->removeDir($dir);
        }
        $this->tempDirs = [];
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = "$dir/$entry";
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
