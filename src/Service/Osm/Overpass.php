<?php

declare(strict_types=1);

namespace App\Service\Osm;

use App\Model\Place;
use App\Repository\CacheRepository;

/**
 * Finds named places around a coordinate via the Overpass API.
 */
final class Overpass
{
    public const MIN_RADIUS = 10;
    public const MAX_RADIUS = 1000;

    public function __construct(
        private HttpClient $http,
        private CacheRepository $cache,
        private string $endpoint,
        private int $ttl,
    ) {
    }

    /** @return list<Place> sorted by distance */
    public function nearby(float $lat, float $lon, int $radius): array
    {
        $radius = max(self::MIN_RADIUS, min(self::MAX_RADIUS, $radius));
        // ~11 m grid: neighbouring requests share a cache entry.
        $key = sprintf('overpass:%.4f,%.4f,%d', $lat, $lon, $radius);
        $json = $this->cache->remember($key, $this->ttl, fn (): string => $this->http->post($this->endpoint, [
            'data' => self::query($lat, $lon, $radius),
        ]));

        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['elements']) || !is_array($data['elements'])) {
            throw new OsmException('Overpass hat keine gültige Antwort geliefert.');
        }
        return self::parse($data['elements'], $lat, $lon);
    }

    public static function query(float $lat, float $lon, int $radius): string
    {
        $around = sprintf('(around:%d,%.6F,%.6F)', $radius, $lat, $lon);
        $parts = [];
        foreach (Categories::KEYS as $key) {
            $filter = sprintf('["name"]["%s"]', $key);
            if (isset(Categories::EXCLUDED[$key])) {
                $filter .= sprintf('["%s"!~"^(%s)$"]', $key, Categories::EXCLUDED[$key]);
            }
            $parts[] = "  nwr$around$filter;";
        }
        return "[out:json][timeout:15];\n(\n" . implode("\n", $parts) . "\n);\nout tags center qt;";
    }

    /**
     * @param array<mixed> $elements Overpass "elements"
     * @return list<Place>
     */
    public static function parse(array $elements, float $lat, float $lon): array
    {
        $places = [];
        foreach ($elements as $el) {
            if (!is_array($el) || !isset($el['type'], $el['id'], $el['tags']['name'])) {
                continue;
            }
            $pLat = $el['lat'] ?? $el['center']['lat'] ?? null;
            $pLon = $el['lon'] ?? $el['center']['lon'] ?? null;
            if (!is_numeric($pLat) || !is_numeric($pLon)) {
                continue;
            }
            /** @var array<string, string> $tags */
            $tags = $el['tags'];
            $key = $el['type'] . '/' . $el['id'];
            $places[$key] ??= new Place(
                name: (string) $tags['name'],
                lat: (float) $pLat,
                lon: (float) $pLon,
                osmType: (string) $el['type'],
                osmId: (int) $el['id'],
                category: Categories::label($tags),
                address: self::address($tags),
                distance: Geo::distance($lat, $lon, (float) $pLat, (float) $pLon),
            );
        }
        $places = array_values($places);
        usort($places, static fn (Place $a, Place $b): int => $a->distance <=> $b->distance);
        return $places;
    }

    /** @param array<string, string> $tags */
    private static function address(array $tags): ?string
    {
        $street = trim(($tags['addr:street'] ?? '') . ' ' . ($tags['addr:housenumber'] ?? ''));
        return $street !== '' ? $street : null;
    }
}
