<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Thin wrapper around PHP sessions with hardened cookie settings.
 * Session files live in data/sessions so shared-hosting GC does not wipe them early.
 */
final class Session
{
    private const LIFETIME = 60 * 60 * 24 * 30; // 30 days – personal app on a phone

    private bool $started = false;

    public function __construct(
        private string $savePath,
        private bool $secure,
    ) {
    }

    public function start(): void
    {
        if ($this->started || PHP_SAPI === 'cli') {
            $this->started = true;
            return;
        }
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0700, true);
        }
        session_save_path($this->savePath);
        session_name('checkin_sid');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) self::LIFETIME);
        session_set_cookie_params([
            'lifetime' => self::LIFETIME,
            'path' => '/',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        $this->start();
        if (PHP_SAPI !== 'cli') {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        if (PHP_SAPI !== 'cli') {
            setcookie(session_name() ?: 'checkin_sid', '', ['expires' => time() - 3600, 'path' => '/']);
            session_destroy();
        }
        $this->started = false;
    }

    public function flash(string $type, string $message): void
    {
        $messages = $this->get('_flash', []);
        $messages[] = ['type' => $type, 'message' => $message];
        $this->set('_flash', $messages);
    }

    /** @return list<array{type: string, message: string}> */
    public function takeFlash(): array
    {
        $messages = $this->get('_flash', []);
        $this->remove('_flash');
        return $messages;
    }

    public function csrfToken(): string
    {
        $token = $this->get('_csrf');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            $this->set('_csrf', $token);
        }
        return $token;
    }

    public function validCsrf(string $token): bool
    {
        $expected = $this->get('_csrf');
        return is_string($expected) && $token !== '' && hash_equals($expected, $token);
    }
}
