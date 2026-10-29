<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Key/value cache with TTL in SQLite, used for OSM API responses.
 */
final class CacheRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function get(string $key, ?int $now = null): ?string
    {
        $stmt = $this->pdo->prepare('SELECT value FROM osm_cache WHERE key = ? AND expires_at > ?');
        $stmt->execute([$key, $now ?? time()]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    public function set(string $key, string $value, int $ttl, ?int $now = null): void
    {
        $now ??= time();
        $this->pdo->prepare('DELETE FROM osm_cache WHERE expires_at <= ?')->execute([$now]);
        $stmt = $this->pdo->prepare(
            'INSERT INTO osm_cache (key, value, expires_at) VALUES (?, ?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, expires_at = excluded.expires_at'
        );
        $stmt->execute([$key, $value, $now + $ttl]);
    }

    /**
     * Return the cached value or compute, store and return it.
     *
     * @param callable(): string $compute
     */
    public function remember(string $key, int $ttl, callable $compute): string
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }
        $value = $compute();
        $this->set($key, $value, $ttl);
        return $value;
    }
}
