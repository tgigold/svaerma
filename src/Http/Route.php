<?php

declare(strict_types=1);

namespace App\Http;

final class Route
{
    /**
     * @param array{class-string, string} $handler
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly string $regex,
        public readonly array $handler,
        public readonly array $options,
    ) {
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
}
