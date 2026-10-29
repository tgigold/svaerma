<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Checkin;
use App\Repository\CheckinRepository;

/**
 * Full export of all check-ins as JSON or GeoJSON (RFC 7946).
 */
final class Exporter
{
    public function __construct(
        private CheckinRepository $checkins,
        private string $baseUrl,
    ) {
    }

    /** @return array<string, mixed> */
    public function json(): array
    {
        return [
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'count' => $this->checkins->count(),
            'checkins' => array_map($this->row(...), $this->all()),
        ];
    }

    /** @return array<string, mixed> */
    public function geoJson(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => array_map(fn (Checkin $c): array => [
                'type' => 'Feature',
                'id' => $c->id,
                // GeoJSON order is [longitude, latitude].
                'geometry' => ['type' => 'Point', 'coordinates' => [$c->lon, $c->lat]],
                'properties' => array_diff_key($this->row($c), ['lat' => 0, 'lon' => 0]),
            ], $this->all()),
        ];
    }

    /** @return list<Checkin> oldest first */
    private function all(): array
    {
        return array_reverse($this->checkins->page(PHP_INT_MAX, 0));
    }

    /** @return array<string, mixed> */
    private function row(Checkin $c): array
    {
        return [
            'id' => $c->id,
            'place_name' => $c->placeName,
            'lat' => $c->lat,
            'lon' => $c->lon,
            'osm_type' => $c->osmType,
            'osm_id' => $c->osmId,
            'checked_in_at' => $c->checkedInAt->format('Y-m-d\TH:i:s\Z'),
            'local_time' => $c->localTime()->format(DATE_ATOM),
            'timezone' => $c->timezone,
            'note' => $c->note,
            'source' => $c->source,
            'external_id' => $c->externalId,
            'venue_ref' => $c->venueRef,
            'url' => $this->baseUrl . $c->path(),
            'created_at' => $c->createdAt,
            'updated_at' => $c->updatedAt,
        ];
    }
}
