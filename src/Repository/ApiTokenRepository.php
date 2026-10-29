<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * API tokens: the plain token is shown once on creation, the database only keeps
 * its SHA-256 hash (tokens are long random strings, so a fast hash is sufficient).
 */
final class ApiTokenRepository
{
    public const PREFIX = 'svm_';

    public function __construct(private PDO $pdo)
    {
    }

    /** @return string the plain token – show it to the user exactly once */
    public function create(string $name): string
    {
        $token = self::PREFIX . bin2hex(random_bytes(24));
        $stmt = $this->pdo->prepare('INSERT INTO api_tokens (name, token_hash, created_at) VALUES (?, ?, ?)');
        $stmt->execute([$name, self::hash($token), gmdate('Y-m-d H:i:s')]);
        return $token;
    }

    /** @return int|null token id if the token is valid and not revoked */
    public function verify(string $token): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM api_tokens WHERE token_hash = ? AND revoked_at IS NULL');
        $stmt->execute([self::hash($token)]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            return null;
        }
        $this->pdo->prepare('UPDATE api_tokens SET last_used_at = ? WHERE id = ?')->execute([gmdate('Y-m-d H:i:s'), $id]);
        return (int) $id;
    }

    public function revoke(int $id): void
    {
        $this->pdo->prepare('UPDATE api_tokens SET revoked_at = ? WHERE id = ? AND revoked_at IS NULL')
            ->execute([gmdate('Y-m-d H:i:s'), $id]);
    }

    /** @return list<array{id: int, name: string, created_at: string, last_used_at: ?string, revoked_at: ?string}> */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT id, name, created_at, last_used_at, revoked_at FROM api_tokens ORDER BY revoked_at IS NOT NULL, id DESC')->fetchAll();
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'created_at' => (string) $r['created_at'],
            'last_used_at' => $r['last_used_at'] !== null ? (string) $r['last_used_at'] : null,
            'revoked_at' => $r['revoked_at'] !== null ? (string) $r['revoked_at'] : null,
        ], $rows);
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
