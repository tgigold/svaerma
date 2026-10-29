<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, string> $headers Lower-case header names
     * @param array<string, mixed> $files
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly array $headers = [],
        public readonly string $ip = '0.0.0.0',
        public readonly bool $https = false,
        public readonly string $body = '',
        public readonly array $files = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $key => $name) {
            if (isset($_SERVER[$key])) {
                $headers[$name] = (string) $_SERVER[$key];
            }
        }
        // Some Apache/CGI setups strip the Authorization header unless passed via env.
        if (!isset($headers['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($headers['x-forwarded-proto'] ?? '') === 'https';

        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : '/';

        return new self(
            method: strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            path: $path === '' ? '/' : $path,
            query: $_GET,
            post: $_POST,
            headers: $headers,
            ip: (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            https: $https,
            body: (string) file_get_contents('php://input'),
            files: $_FILES,
        );
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /** @return array<string, mixed>|null */
    public function json(): ?array
    {
        $data = json_decode($this->body, true);
        return is_array($data) ? $data : null;
    }

    public function wantsJson(): bool
    {
        return str_starts_with($this->path, '/api/')
            || str_contains((string) $this->header('accept'), 'application/json');
    }
}
