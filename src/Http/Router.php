<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Minimal router. Patterns use {name} or {name:regex} placeholders, e.g.
 * "/checkin/{id:\d+}{slug:(?:-[^/]*)?}".
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    /**
     * @param array{class-string, string} $handler
     * @param array<string, mixed> $options e.g. ['public' => true]
     */
    public function add(string $method, string $pattern, array $handler, array $options = []): void
    {
        $this->routes[] = new Route(strtoupper($method), $pattern, self::compile($pattern), $handler, $options);
    }

    /**
     * @param array{class-string, string} $handler
     * @param array<string, mixed> $options
     */
    public function get(string $pattern, array $handler, array $options = []): void
    {
        $this->add('GET', $pattern, $handler, $options);
    }

    /**
     * @param array{class-string, string} $handler
     * @param array<string, mixed> $options
     */
    public function post(string $pattern, array $handler, array $options = []): void
    {
        $this->add('POST', $pattern, $handler, $options);
    }

    /**
     * @return array{route: Route, params: array<string, string>}|null null if nothing matches
     */
    public function match(string $method, string $path): ?array
    {
        $lookup = strtoupper($method) === 'HEAD' ? 'GET' : strtoupper($method);
        foreach ($this->routes as $route) {
            if ($route->method !== $lookup) {
                continue;
            }
            if (preg_match($route->regex, $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                return ['route' => $route, 'params' => array_map('strval', $params)];
            }
        }
        return null;
    }

    /** @return list<string> Methods allowed for a path (for 405 responses) */
    public function allowedMethods(string $path): array
    {
        $methods = [];
        foreach ($this->routes as $route) {
            if (preg_match($route->regex, $path)) {
                $methods[] = $route->method;
            }
        }
        return array_values(array_unique($methods));
    }

    private static function compile(string $pattern): string
    {
        $parts = preg_split(
            '/(\{[a-z_][a-z0-9_]*(?::(?:[^{}]|\{[^{}]*\})+)?\})/i',
            $pattern,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        ) ?: [];
        $regex = '';
        foreach ($parts as $part) {
            if (preg_match('/^\{([a-z_][a-z0-9_]*)(?::(.+))?\}$/is', $part, $m)) {
                $regex .= '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }
        return '#^' . $regex . '$#u';
    }
}
