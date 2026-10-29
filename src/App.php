<?php

declare(strict_types=1);

namespace App;

use App\Database\Backup;
use App\Database\Connection;
use App\Database\Migrator;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Http\Session;
use App\Repository\CacheRepository;
use App\Repository\CheckinRepository;
use App\Repository\LoginAttemptRepository;
use App\Repository\SettingsRepository;
use App\Service\Auth;
use App\Service\CheckinService;
use App\Service\Osm\HttpClient;
use App\Service\Osm\Overpass;
use App\Service\Osm\PlaceSearch;
use App\Service\Osm\Throttle;
use PDO;

/**
 * Composition root and request kernel. Services are created lazily on first use.
 * Add a getter here when a new feature needs a new repository or service.
 */
final class App
{
    public const VERSION = '0.2.0';
    public const DEFAULT_RADIUS = 150;

    private ?PDO $pdo = null;
    private ?Session $session = null;
    private ?View $view = null;
    private ?Router $router = null;

    public function __construct(public readonly Config $config)
    {
    }

    // ---- Services -------------------------------------------------------

    public function pdo(): PDO
    {
        return $this->pdo ??= Connection::open((string) $this->config->get('db_path'));
    }

    public function dataDir(): string
    {
        return (string) $this->config->get('data_dir');
    }

    public function session(): Session
    {
        return $this->session ??= new Session($this->dataDir() . '/sessions', !$this->config->isDev());
    }

    public function view(): View
    {
        return $this->view ??= new View(APP_ROOT . '/templates');
    }

    public function router(): Router
    {
        if ($this->router === null) {
            $this->router = new Router();
            (require APP_ROOT . '/src/routes.php')($this->router);
        }
        return $this->router;
    }

    public function settings(): SettingsRepository
    {
        return new SettingsRepository($this->pdo());
    }

    public function auth(): Auth
    {
        return new Auth($this->session(), $this->settings(), new LoginAttemptRepository($this->pdo()));
    }

    public function backup(): Backup
    {
        return new Backup($this->pdo(), $this->dataDir() . '/backups');
    }

    public function migrator(): Migrator
    {
        return new Migrator($this->pdo(), APP_ROOT . '/migrations', $this->backup());
    }

    public function checkins(): CheckinRepository
    {
        return new CheckinRepository($this->pdo());
    }

    public function checkinService(): CheckinService
    {
        return new CheckinService($this->checkins(), (string) $this->config->get('timezone'));
    }

    /** Search radius for nearby places in metres (user setting). */
    public function nearbyRadius(): int
    {
        return (int) ($this->settings()->get('nearby_radius') ?? self::DEFAULT_RADIUS);
    }

    public function http(): HttpClient
    {
        // Fair use: identify the app and give a contact address.
        $ua = sprintf(
            'CheckinLog/%s (+%s; %s)',
            self::VERSION,
            (string) $this->config->get('base_url'),
            (string) $this->config->get('contact_email'),
        );
        return new HttpClient($ua, (int) $this->config->get('osm.timeout'));
    }

    public function overpass(): Overpass
    {
        return new Overpass(
            $this->http(),
            new CacheRepository($this->pdo()),
            (string) $this->config->get('osm.overpass_url'),
            (int) $this->config->get('osm.cache_ttl'),
        );
    }

    public function placeSearch(): PlaceSearch
    {
        return new PlaceSearch(
            $this->http(),
            new CacheRepository($this->pdo()),
            (string) $this->config->get('osm.search_provider'),
            (string) $this->config->get('osm.photon_url'),
            (string) $this->config->get('osm.nominatim_url'),
            new Throttle($this->dataDir() . '/nominatim.lock', 1.0),
            (int) $this->config->get('osm.cache_ttl'),
        );
    }

    // ---- Kernel ---------------------------------------------------------

    public function handle(Request $request): Response
    {
        try {
            $response = $this->dispatch($request);
        } catch (\Throwable $e) {
            $this->log($e);
            $response = $this->error($request, 500, $this->config->isDev() ? (string) $e : null);
        }
        return $this->secure($response);
    }

    private function dispatch(Request $request): Response
    {
        if (!$this->config->isDev() && !$request->https) {
            return Response::redirect('https://' . ($request->header('host') ?? '') . $request->path, 301);
        }

        $match = $this->router()->match($request->method, $request->path);
        if ($match === null) {
            $allowed = $this->router()->allowedMethods($request->path);
            if ($allowed !== []) {
                return $this->error($request, 405)->withHeader('Allow', implode(', ', $allowed));
            }
            return Config::exists() ? $this->error($request, 404) : Response::redirect('/setup');
        }
        $route = $match['route'];

        // Setup routes only exist until config/config.php is written, everything else only after.
        if ($route->option('setup')) {
            if (Config::exists()) {
                return $this->error($request, 404);
            }
        } elseif (!Config::exists()) {
            return Response::redirect('/setup');
        }

        $session = $this->session();
        $session->start();

        if ($request->method === 'POST' && !$route->option('api')
            && !$session->validCsrf($request->input('_csrf'))) {
            return $this->error($request, 400, null, 'Das Formular ist abgelaufen. Bitte lade die Seite neu und versuche es nochmal.');
        }

        if (!$route->option('public') && !$route->option('setup')) {
            if (!$this->auth()->check()) {
                if ($request->wantsJson()) {
                    return Response::json(['error' => 'Nicht angemeldet.'], 401);
                }
                if ($request->method === 'GET') {
                    $session->set('intended', $request->path);
                }
                return Response::redirect('/login');
            }
            if (!$route->option('ignore_migrations') && $this->migrator()->pending() !== []) {
                return $request->wantsJson()
                    ? Response::json(['error' => 'Datenbank-Migration ausstehend.'], 503)
                    : Response::redirect('/admin');
            }
        }

        $this->view()->share('app', $this);
        $this->view()->share('csrf', $session->csrfToken());
        $this->view()->share('currentPath', $request->path);
        $this->view()->share('loggedIn', !$route->option('setup') && $this->auth()->check());

        [$class, $method] = $route->handler;
        $controller = new $class($this, $request);
        return $controller->$method($match['params']);
    }

    public function error(Request $request, int $status, ?string $debug = null, ?string $message = null): Response
    {
        $titles = [400 => 'Ungültige Anfrage', 403 => 'Kein Zugriff', 404 => 'Nicht gefunden', 405 => 'Methode nicht erlaubt', 500 => 'Interner Fehler'];
        $title = $titles[$status] ?? 'Fehler';
        if ($request->wantsJson()) {
            return Response::json(['error' => $message ?? $title], $status);
        }
        try {
            $html = $this->view()->render('errors/error', [
                'status' => $status,
                'title' => $title,
                'message' => $message,
                'debug' => $debug,
                'loggedIn' => false,
            ], 'layout_minimal');
        } catch (\Throwable) {
            $html = '<h1>' . e($title) . '</h1>';
        }
        return Response::html($html, $status);
    }

    private function secure(Response $response): Response
    {
        $tiles = parse_url((string) $this->config->get('osm.tile_url'), PHP_URL_HOST) ?: 'tile.openstreetmap.org';
        $csp = "default-src 'self'; "
            . "script-src 'self' https://cdn.jsdelivr.net; "
            . "style-src 'self' https://cdn.jsdelivr.net; "
            . "font-src 'self' https://cdn.jsdelivr.net; "
            . "img-src 'self' data: https://$tiles https://*.$tiles; "
            . "connect-src 'self'; "
            . "manifest-src 'self'; worker-src 'self'; "
            . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'";

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'same-origin',
            'Permissions-Policy' => 'geolocation=(self), camera=(), microphone=()',
            'Content-Security-Policy' => $csp,
        ];
        if (!$this->config->isDev()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }
        foreach ($headers as $name => $value) {
            $response->headers[$name] ??= $value;
        }
        return $response;
    }

    public function log(\Throwable|string $error): void
    {
        $dir = $this->dataDir() . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $line = sprintf("[%s] %s\n", gmdate('Y-m-d H:i:s'), is_string($error) ? $error : (string) $error);
        @file_put_contents($dir . '/error.log', $line, FILE_APPEND | LOCK_EX);
    }
}
