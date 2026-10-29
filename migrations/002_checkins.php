<?php

declare(strict_types=1);

// Check-ins and the cache for OpenStreetMap API responses.
return static function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE checkins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lat REAL NOT NULL,
        lon REAL NOT NULL,
        place_name TEXT NOT NULL,
        osm_type TEXT NULL CHECK (osm_type IN ('node', 'way', 'relation')),
        osm_id INTEGER NULL,
        checked_in_at TEXT NOT NULL,          -- UTC, 'Y-m-d H:i:s'
        timezone TEXT NOT NULL,               -- IANA zone where the check-in happened
        note TEXT NULL,
        slug TEXT NOT NULL,
        source TEXT NOT NULL CHECK (source IN ('web', 'api', 'swarm')),
        external_id TEXT NULL,                -- e.g. Swarm check-in id, for import deduplication
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        CHECK ((osm_type IS NULL) = (osm_id IS NULL))
    )");
    $pdo->exec('CREATE INDEX idx_checkins_time ON checkins (checked_in_at)');
    $pdo->exec('CREATE INDEX idx_checkins_osm ON checkins (osm_type, osm_id)');
    $pdo->exec('CREATE UNIQUE INDEX idx_checkins_external ON checkins (source, external_id) WHERE external_id IS NOT NULL');

    $pdo->exec('CREATE TABLE osm_cache (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL,
        expires_at INTEGER NOT NULL
    )');
    $pdo->exec('CREATE INDEX idx_osm_cache_expires ON osm_cache (expires_at)');
};
