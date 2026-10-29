<?php

declare(strict_types=1);

/**
 * Escape a value for HTML output. Use in every template echo.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
