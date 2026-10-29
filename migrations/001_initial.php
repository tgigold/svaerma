<?php

declare(strict_types=1);

// Key/value settings (password hash, search radius, …) and login rate limiting.
return static function (PDO $pdo): void {
    $pdo->exec('CREATE TABLE settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        attempted_at INTEGER NOT NULL
    )');
    $pdo->exec('CREATE INDEX idx_login_attempts_time ON login_attempts (attempted_at)');
};
