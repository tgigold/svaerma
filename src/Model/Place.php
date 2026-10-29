<?php

declare(strict_types=1);

namespace App\Model;

/**
 * A place candidate from OpenStreetMap (nearby query or search).
 */
final class Place implements \JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly float $lat,
        public readonly float $lon,
        public readonly ?string $osmType = null,
        public readonly ?int $osmId = null,
        public readonly ?string $category = null,
        public readonly ?string $address = null,
        public readonly ?float $distance = null,
    ) {
    }

    public function withDistance(float $distance): self
    {
        return new self($this->name, $this->lat, $this->lon, $this->osmType, $this->osmId, $this->category, $this->address, $distance);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'osm_type' => $this->osmType,
            'osm_id' => $this->osmId,
            'category' => $this->category,
            'address' => $this->address,
            'distance' => $this->distance !== null ? (int) round($this->distance) : null,
        ];
    }
}
