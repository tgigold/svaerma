<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\Slugger;

final class SluggerTest extends TestCase
{
    public function testGermanUmlautsAndSpecialChars(): void
    {
        self::assertSame('cafe-mueller-und-soehne', Slugger::slugify('Café Müller & Söhne'));
        self::assertSame('strasse-des-17-juni', Slugger::slugify('Straße des 17. Juni'));
        self::assertSame('oerebro-slott', Slugger::slugify('Örebro slott'));
    }

    public function testEmptyAndNonLatin(): void
    {
        self::assertSame('', Slugger::slugify('!!!'));
        self::assertNotSame('', Slugger::slugify('東京タワー'), 'Transliterated when intl is available');
    }

    public function testTruncatesAtWordBoundary(): void
    {
        $slug = Slugger::slugify(str_repeat('wort ', 30));
        self::assertLessThanOrEqual(60, strlen($slug));
        self::assertStringEndsWith('wort', $slug);
    }
}
