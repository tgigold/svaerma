<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\Session;
use App\Repository\LoginAttemptRepository;
use App\Repository\SettingsRepository;

/**
 * Single-user authentication with simple brute-force protection:
 * at most MAX_PER_IP failures per IP and MAX_GLOBAL failures overall per WINDOW.
 */
final class Auth
{
    public const WINDOW = 15 * 60;
    public const MAX_PER_IP = 5;
    public const MAX_GLOBAL = 25;

    public function __construct(
        private Session $session,
        private SettingsRepository $settings,
        private LoginAttemptRepository $attempts,
    ) {
    }

    public function check(): bool
    {
        return $this->session->get('auth') === true;
    }

    public function isLocked(string $ip, ?int $now = null): bool
    {
        $since = ($now ?? time()) - self::WINDOW;
        return $this->attempts->countSince($since, $ip) >= self::MAX_PER_IP
            || $this->attempts->countSince($since) >= self::MAX_GLOBAL;
    }

    /**
     * @return 'ok'|'invalid'|'locked'
     */
    public function attempt(string $password, string $ip, ?int $now = null): string
    {
        $now ??= time();
        $this->attempts->purgeOlderThan($now - self::WINDOW);
        if ($this->isLocked($ip, $now)) {
            return 'locked';
        }

        $hash = (string) $this->settings->get('password_hash', '');
        if ($hash === '' || !password_verify($password, $hash)) {
            $this->attempts->record($ip, $now);
            return 'invalid';
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->settings->set('password_hash', password_hash($password, PASSWORD_DEFAULT));
        }
        $this->attempts->clear($ip);
        $this->session->regenerate();
        $this->session->set('auth', true);
        return 'ok';
    }

    public function setPassword(string $password): void
    {
        $this->settings->set('password_hash', password_hash($password, PASSWORD_DEFAULT));
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
