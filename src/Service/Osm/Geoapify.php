<?php

declare(strict_types=1);

namespace App\Service\Osm;

use App\Model\Place;
use App\Repository\CacheRepository;

/**
 * Optional nearby search via the Geoapify Places API (OSM data, API key required,
 * free tier 3,000 requests/day, attribution "Powered by Geoapify").
 */
final class Geoapify
{
    /** Top-level Geoapify categories that correspond to places worth checking in to. */
    private const CATEGORIES = 'accommodation,activity,airport,catering,commercial,education,entertainment,healthcare,heritage,leisure,natural,office,public_transport,religion,service,sport,tourism';

    private const TYPES = ['n' => 'node', 'w' => 'way', 'r' => 'relation', 'node' => 'node', 'way' => 'way', 'relation' => 'relation'];

    public function __construct(
        private HttpClient $http,
        private CacheRepository $cache,
        private string $endpoint,
        private string $apiKey,
        private int $ttl,
    ) {
    }

    /** @return list<Place> sorted by distance */
    public function nearby(float $lat, float $lon, int $radius): array
    {
        $key = sprintf('geoapify:%.4f,%.4f,%d', $lat, $lon, $radius);
        $json = $this->cache->remember($key, $this->ttl, fn (): string => $this->http->get($this->endpoint, [
            'categories' => self::CATEGORIES,
            'filter' => sprintf('circle:%.6F,%.6F,%d', $lon, $lat, $radius),
            'bias' => sprintf('proximity:%.6F,%.6F', $lon, $lat),
            'limit' => 50,
            'lang' => 'de',
            'apiKey' => $this->apiKey,
        ]));
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['features']) || !is_array($data['features'])) {
            throw new OsmException('Geoapify hat keine gültige Antwort geliefert.');
        }
        return self::parse($data['features'], $lat, $lon);
    }

    /**
     * @param array<mixed> $features
     * @return list<Place>
     */
    public static function parse(array $features, float $lat, float $lon): array
    {
        $places = [];
        foreach ($features as $f) {
            $p = is_array($f) ? ($f['properties'] ?? null) : null;
            if (!is_array($p) || !isset($p['name'], $p['lat'], $p['lon']) || trim((string) $p['name']) === '') {
                continue;
            }
            $raw = $p['datasource']['raw'] ?? [];
            $type = self::TYPES[strtolower((string) ($raw['osm_type'] ?? ''))] ?? null;
            $id = isset($raw['osm_id']) && is_numeric($raw['osm_id']) ? abs((int) $raw['osm_id']) : null;
            if ($type === null || $id === null) {
                $type = $id = null;
            }
            $street = trim(((string) ($p['street'] ?? '')) . ' ' . ((string) ($p['housenumber'] ?? '')));

            $place = new Place(
                name: (string) $p['name'],
                lat: (float) $p['lat'],
                lon: (float) $p['lon'],
                osmType: $type,
                osmId: $id,
                category: self::category($p['categories'] ?? []),
                address: $street !== '' ? $street : null,
                distance: Geo::distance($lat, $lon, (float) $p['lat'], (float) $p['lon']),
            );
            $places[$type !== null ? "$type/$id" : 'x' . count($places)] ??= $place;
        }
        $places = array_values($places);
        usort($places, static fn (Place $a, Place $b): int => $a->distance <=> $b->distance);
        return $places;
    }

    /** Most specific category, e.g. "catering.cafe" → "Café" via the OSM label table. */
    private static function category(mixed $categories): ?string
    {
        if (!is_array($categories) || $categories === []) {
            return null;
        }
        usort($categories, static fn ($a, $b): int => substr_count((string) $b, '.') <=> substr_count((string) $a, '.'));
        $parts = explode('.', (string) $categories[0]);
        $value = end($parts);
        foreach (['amenity', 'shop', 'tourism', 'leisure'] as $key) {
            $label = Categories::label([$key => $value]);
            if ($label !== null && $label !== ucfirst(str_replace('_', ' ', $value))) {
                return $label;
            }
        }
        return ucfirst(str_replace('_', ' ', $value));
    }
}
