<?php

declare(strict_types=1);

// API bearer tokens. Only a SHA-256 hash of each token is stored.
return static function (PDO $pdo): void {
    $pdo->exec('CREATE TABLE api_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        token_hash TEXT NOT NULL UNIQUE,
        created_at TEXT NOT NULL,
        last_used_at TEXT NULL,
        revoked_at TEXT NULL
    )');
};
