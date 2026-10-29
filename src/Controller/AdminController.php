<?php

declare(strict_types=1);

namespace App\Controller;

use App\App;
use App\Http\Response;

final class AdminController extends Controller
{
    /** @param array<string, string> $params */
    public function index(array $params): Response
    {
        $migrator = $this->app->migrator();
        $dbFile = (string) $this->app->config->get('db_path');
        return $this->render('admin/index', [
            'version' => App::VERSION,
            'applied' => $migrator->applied(),
            'pending' => $migrator->pending(),
            'backups' => array_map(
                static fn (string $f): array => ['name' => basename($f), 'size' => (int) filesize($f), 'time' => (int) filemtime($f)],
                $this->app->backup()->list(),
            ),
            'dbSize' => is_file($dbFile) ? (int) filesize($dbFile) : 0,
            'phpVersion' => PHP_VERSION,
            'sqliteVersion' => (string) $this->app->pdo()->query('SELECT sqlite_version()')->fetchColumn(),
        ]);
    }

    /** @param array<string, string> $params */
    public function migrate(array $params): Response
    {
        try {
            $result = $this->app->migrator()->migrate();
            if ($result['applied'] === []) {
                $this->flash('info', 'Keine ausstehenden Migrationen.');
            } else {
                $msg = 'Migriert: ' . implode(', ', $result['applied']) . '.';
                if ($result['backup'] !== null) {
                    $msg .= ' Backup: ' . basename($result['backup']);
                }
                $this->flash('success', $msg);
            }
        } catch (\Throwable $e) {
            $this->app->log($e);
            $this->flash('danger', 'Migration fehlgeschlagen, Änderungen wurden zurückgerollt. Details stehen in data/logs/error.log.');
        }
        return $this->redirect('/admin');
    }

    /** @param array<string, string> $params */
    public function backup(array $params): Response
    {
        $file = $this->app->backup()->create('manual');
        $this->flash('success', 'Backup erstellt: ' . basename($file));
        return $this->redirect('/admin');
    }
}
