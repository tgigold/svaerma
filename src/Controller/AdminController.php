<?php

declare(strict_types=1);

namespace App\Controller;

use App\App;
use App\Http\Response;
use App\Repository\CacheRepository;
use App\Service\Osm\OsmException;
use App\Service\Osm\Overpass;

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

    /**
     * Connectivity check for all OSM endpoints, bypassing the cache.
     *
     * @param array<string, string> $params
     */
    public function osmTest(array $params): Response
    {
        $http = $this->app->http();
        $checks = [];
        foreach ($this->app->overpassEndpoints() as $url) {
            $checks['Overpass ' . preg_replace('#^https?://#', '', $url)] = function () use ($http, $url): void {
                $overpass = new Overpass($http, new CacheRepository($this->app->pdo()), [$url], 0);
                $overpass->fetch(Overpass::query(52.5163, 13.3777, 50));
            };
        }
        $provider = (string) $this->app->config->get('osm.search_provider');
        $checks['Suche (' . $provider . ')'] = function () use ($http, $provider): void {
            $body = $provider === 'nominatim'
                ? $http->get((string) $this->app->config->get('osm.nominatim_url'), ['q' => 'Berlin', 'format' => 'jsonv2', 'limit' => 1])
                : $http->get((string) $this->app->config->get('osm.photon_url'), ['q' => 'Berlin', 'limit' => 1]);
            if (!is_array(json_decode($body, true))) {
                throw new OsmException('ungültige Antwort');
            }
        };

        foreach ($checks as $name => $check) {
            $start = microtime(true);
            try {
                $check();
                $this->flash('success', sprintf('%s: OK (%d ms)', $name, (microtime(true) - $start) * 1000));
            } catch (OsmException $e) {
                $this->flash('danger', sprintf('%s: %s (%d ms)', $name, $e->getMessage(), (microtime(true) - $start) * 1000));
            }
        }
        return $this->redirect('/admin');
    }
}
