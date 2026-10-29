<?php

declare(strict_types=1);

namespace App\Tests;

use App\Database\Backup;
use App\Database\Connection;
use App\Database\Migrator;

final class MigratorTest extends TestCase
{
    private function writeMigration(string $dir, string $name, string $body): void
    {
        file_put_contents("$dir/$name.php", "<?php\nreturn static function (PDO \$pdo): void {\n$body\n};\n");
    }

    public function testAppliesPendingMigrationsInOrder(): void
    {
        $dir = $this->tempDir();
        $this->writeMigration($dir, '002_second', '$pdo->exec("INSERT INTO t (v) VALUES (2)");');
        $this->writeMigration($dir, '001_first', '$pdo->exec("CREATE TABLE t (v INTEGER)");');
        $this->writeMigration($dir, '010_tenth', '$pdo->exec("INSERT INTO t (v) VALUES (10)");');
        file_put_contents("$dir/README.txt", 'ignored');

        $pdo = Connection::open(':memory:');
        $migrator = new Migrator($pdo, $dir);

        self::assertSame(['001_first', '002_second', '010_tenth'], $migrator->pending());
        $result = $migrator->migrate();

        self::assertSame(['001_first', '002_second', '010_tenth'], $result['applied']);
        self::assertSame([], $migrator->pending());
        self::assertSame([2, 10], array_map('intval', $pdo->query('SELECT v FROM t ORDER BY v')->fetchAll(\PDO::FETCH_COLUMN)));
    }

    public function testIsIdempotent(): void
    {
        $dir = $this->tempDir();
        $this->writeMigration($dir, '001_first', '$pdo->exec("CREATE TABLE t (v INTEGER)");');
        $pdo = Connection::open(':memory:');
        $migrator = new Migrator($pdo, $dir);

        $migrator->migrate();
        self::assertSame([], $migrator->migrate()['applied']);
    }

    public function testFailedMigrationIsRolledBackAndStopsTheRun(): void
    {
        $dir = $this->tempDir();
        $this->writeMigration($dir, '001_first', '$pdo->exec("CREATE TABLE t (v INTEGER)");');
        $this->writeMigration($dir, '002_broken', '$pdo->exec("CREATE TABLE half (x INTEGER)"); $pdo->exec("THIS IS NOT SQL");');
        $this->writeMigration($dir, '003_after', '$pdo->exec("CREATE TABLE after (x INTEGER)");');

        $pdo = Connection::open(':memory:');
        $migrator = new Migrator($pdo, $dir);

        try {
            $migrator->migrate();
            self::fail('Expected exception');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('002_broken', $e->getMessage());
        }

        self::assertSame(['001_first'], $migrator->applied());
        self::assertSame(['002_broken', '003_after'], $migrator->pending());
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(\PDO::FETCH_COLUMN);
        self::assertNotContains('half', $tables, 'DDL of the failed migration must be rolled back');
        self::assertNotContains('after', $tables);
    }

    public function testCreatesBackupBeforeMigratingExistingDatabase(): void
    {
        $dir = $this->tempDir();
        $dbFile = "$dir/app.sqlite";
        $this->writeMigration($dir, '001_first', '$pdo->exec("CREATE TABLE t (v INTEGER)"); $pdo->exec("INSERT INTO t VALUES (42)");');

        $pdo = Connection::open($dbFile);
        $backup = new Backup($pdo, "$dir/backups");
        $migrator = new Migrator($pdo, $dir, $backup);

        // Fresh database: nothing to back up yet.
        self::assertNull($migrator->migrate()['backup']);

        $this->writeMigration($dir, '002_second', '$pdo->exec("DELETE FROM t");');
        $result = $migrator->migrate();

        self::assertNotNull($result['backup']);
        self::assertFileExists($result['backup']);
        $copy = Connection::open($result['backup']);
        self::assertSame(42, (int) $copy->query('SELECT v FROM t')->fetchColumn(), 'Backup holds the pre-migration state');
    }

    public function testRealMigrationsApplyCleanly(): void
    {
        $pdo = $this->migratedPdo();
        self::assertSame([], (new Migrator($pdo, APP_ROOT . '/migrations'))->pending());
    }
}
