<?php

declare(strict_types=1);

namespace App\Service\Osm;

use App\Model\Place;

/**
 * Nearby places with a fallback chain: Overpass → Geoapify (if configured) → Photon reverse.
 */
final class NearbySearch
{
    private const NOTICES = [
        'geoapify' => 'Overpass ist nicht erreichbar, die Liste kommt von Geoapify.',
        'photon' => 'Overpass ist nicht erreichbar, die Liste kommt von Photon und ist evtl. unvollständig.',
    ];

    /**
     * @param array<string, \Closure(float, float, int): list<Place>> $providers in order of preference
     * @param \Closure(string): void $log
     */
    public function __construct(
        private array $providers,
        private \Closure $log,
    ) {
    }

    /**
     * @return array{source: string, places: list<Place>, notice: ?string}
     * @throws OsmException when every provider failed (message lists all reasons)
     */
    public function find(float $lat, float $lon, int $radius): array
    {
        $errors = [];
        foreach ($this->providers as $name => $provider) {
            try {
                return [
                    'source' => $name,
                    'places' => $provider($lat, $lon, $radius),
                    'notice' => self::NOTICES[$name] ?? null,
                ];
            } catch (OsmException $e) {
                ($this->log)(ucfirst($name) . ': ' . $e->getMessage());
                $errors[] = ucfirst($name) . ': ' . $e->getMessage();
            }
        }
        throw new OsmException(implode(' | ', $errors));
    }
}
