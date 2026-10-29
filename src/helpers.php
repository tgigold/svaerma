<?php

declare(strict_types=1);

/**
 * Escape a value for HTML output. Use in every template echo.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL of a file in public/assets with the app version appended, so browsers
 * fetch fresh copies after every update despite long cache lifetimes.
 */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/') . '?v=' . App\App::VERSION;
}
