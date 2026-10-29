<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class LoginAttemptRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function record(string $ip, int $time): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)');
        $stmt->execute([$ip, $time]);
    }

    public function countSince(int $since, ?string $ip = null): int
    {
        if ($ip === null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE attempted_at >= ?');
            $stmt->execute([$since]);
        } else {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?');
            $stmt->execute([$ip, $since]);
        }
        return (int) $stmt->fetchColumn();
    }

    public function clear(string $ip): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE ip = ?');
        $stmt->execute([$ip]);
    }

    public function purgeOlderThan(int $time): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?');
        $stmt->execute([$time]);
    }
}
