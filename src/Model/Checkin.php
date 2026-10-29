<?php

declare(strict_types=1);

namespace App\Model;

final class Checkin
{
    public function __construct(
        public readonly int $id,
        public readonly float $lat,
        public readonly float $lon,
        public readonly string $placeName,
        public readonly ?string $osmType,
        public readonly ?int $osmId,
        public readonly \DateTimeImmutable $checkedInAt,
        public readonly string $timezone,
        public readonly ?string $note,
        public readonly string $slug,
        public readonly string $source,
        public readonly ?string $externalId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $venueRef = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            lat: (float) $row['lat'],
            lon: (float) $row['lon'],
            placeName: (string) $row['place_name'],
            osmType: $row['osm_type'] !== null ? (string) $row['osm_type'] : null,
            osmId: $row['osm_id'] !== null ? (int) $row['osm_id'] : null,
            checkedInAt: new \DateTimeImmutable((string) $row['checked_in_at'], new \DateTimeZone('UTC')),
            timezone: (string) $row['timezone'],
            note: $row['note'] !== null ? (string) $row['note'] : null,
            slug: (string) $row['slug'],
            source: (string) $row['source'],
            externalId: $row['external_id'] !== null ? (string) $row['external_id'] : null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            venueRef: isset($row['venue_ref']) ? (string) $row['venue_ref'] : null,
        );
    }

    /** Check-in time in the timezone where it happened. */
    public function localTime(): \DateTimeImmutable
    {
        try {
            return $this->checkedInAt->setTimezone(new \DateTimeZone($this->timezone));
        } catch (\Exception) {
            return $this->checkedInAt;
        }
    }

    public function path(): string
    {
        return self::pathFor($this->id, $this->slug);
    }

    /** Canonical URL path, e.g. "/checkin/123-cafe-xyz". */
    public static function pathFor(int $id, string $slug): string
    {
        return '/checkin/' . $id . ($slug !== '' ? '-' . $slug : '');
    }

    /**
     * Where to redirect when a URL was requested with a stale or missing slug.
     * The ID is authoritative; the slug part is cosmetic.
     *
     * @param string $requestedSlug the slug part of the URL including its leading "-", or ""
     */
    public function canonicalRedirect(string $requestedSlug): ?string
    {
        $expected = $this->slug !== '' ? '-' . $this->slug : '';
        return $requestedSlug === $expected ? null : $this->path();
    }

    public function osmUrl(): ?string
    {
        return $this->osmType !== null ? "https://www.openstreetmap.org/{$this->osmType}/{$this->osmId}" : null;
    }
}
