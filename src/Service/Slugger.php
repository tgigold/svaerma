<?php

declare(strict_types=1);

namespace App\Service;

final class Slugger
{
    private const MAP = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss', '&' => ' und '];

    /** "Café Müller & Söhne" → "cafe-mueller-und-soehne" (max. 60 chars, may be empty). */
    public static function slugify(string $text, int $maxLength = 60): string
    {
        $text = strtr($text, self::MAP);
        if (function_exists('transliterator_transliterate')) {
            $text = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
        } elseif (function_exists('iconv')) {
            $text = (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        }
        $text = strtolower($text);
        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        if (strlen($text) > $maxLength) {
            $text = rtrim(substr($text, 0, $maxLength), '-');
            // Do not cut in the middle of a word if there is a reasonable break.
            $cut = strrpos($text, '-');
            if ($cut !== false && $cut > $maxLength / 2) {
                $text = substr($text, 0, $cut);
            }
        }
        return $text;
    }
}
