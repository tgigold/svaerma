<?php

declare(strict_types=1);

namespace App\Tests;

use App\Format;

final class FormatTest extends TestCase
{
    public function testGermanDay(): void
    {
        self::assertSame('Donnerstag, 29. Oktober 2026', Format::day(new \DateTimeImmutable('2026-10-29')));
        self::assertSame('Sonntag, 1. März 2026', Format::day(new \DateTimeImmutable('2026-03-01')));
    }

    public function testNumber(): void
    {
        self::assertSame('12.345', Format::number(12345));
    }
}
