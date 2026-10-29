<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response;
use App\Model\Place;
use App\Service\Osm\Geo;
use App\Service\Osm\OsmException;
use App\Service\Osm\Overpass;

/**
 * JSON endpoints for the check-in page (session-authenticated).
 */
final class PlaceController extends Controller
{
    /** @param array<string, string> $params */
    public function nearby(array $params): Response
    {
        $coords = $this->coordinates();
        if ($coords === null) {
            return Response::json(['error' => 'Ungültige Koordinaten.'], 422);
        }
        $radius = max(Overpass::MIN_RADIUS, min(Overpass::MAX_RADIUS, (int) ($this->request->query['radius'] ?? $this->app->nearbyRadius())));
        try {
            $places = $this->app->overpass()->nearby($coords[0], $coords[1], $radius);
            return Response::json(['source' => 'overpass', 'places' => $this->withVisits($places)]);
        } catch (OsmException $e) {
            $this->app->log('Overpass: ' . $e->getMessage());
            $overpassError = $e->getMessage();
        }

        // Fallback: Photon reverse geocoding (fewer places, but a different service).
        try {
            $places = $this->app->placeSearch()->nearby($coords[0], $coords[1], $radius);
        } catch (OsmException $e) {
            $this->app->log('Photon reverse: ' . $e->getMessage());
            return Response::json([
                'error' => 'Weder Overpass noch Photon sind gerade erreichbar. Nutze die Suche oder einen eigenen Ort.',
                'detail' => $overpassError . ' | Photon: ' . $e->getMessage(),
            ], 502);
        }
        return Response::json([
            'source' => 'photon',
            'notice' => 'Overpass ist nicht erreichbar, die Liste kommt von Photon und ist evtl. unvollständig.',
            'places' => $this->withVisits($places),
        ]);
    }

    /** @param array<string, string> $params */
    public function search(array $params): Response
    {
        $coords = $this->coordinates();
        try {
            $places = $this->app->placeSearch()->search($this->request->input('q'), $coords[0] ?? null, $coords[1] ?? null);
        } catch (OsmException $e) {
            $this->app->log('Search: ' . $e->getMessage());
            return Response::json(['error' => 'Die Ortssuche ist gerade nicht erreichbar.', 'detail' => $e->getMessage()], 502);
        }
        return Response::json(['places' => $this->withVisits($places)]);
    }

    /** @return array{0: float, 1: float}|null */
    private function coordinates(): ?array
    {
        $lat = filter_var($this->request->query['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lon = filter_var($this->request->query['lon'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lon === false || !Geo::validCoordinates($lat, $lon)) {
            return null;
        }
        return [$lat, $lon];
    }

    /**
     * Adds "last visited" info to each place.
     *
     * @param list<Place> $places
     * @return list<array<string, mixed>>
     */
    private function withVisits(array $places): array
    {
        $refs = [];
        foreach ($places as $p) {
            if ($p->osmType !== null && $p->osmId !== null) {
                $refs[] = [$p->osmType, $p->osmId];
            }
        }
        $visits = $this->app->checkins()->visitsFor($refs);
        $tz = new \DateTimeZone((string) $this->app->config->get('timezone'));

        return array_map(static function (Place $p) use ($visits, $tz): array {
            $data = $p->jsonSerialize();
            $visit = $visits[$p->osmType . '/' . $p->osmId] ?? null;
            $data['visits'] = $visit['count'] ?? 0;
            $data['last_visit'] = $visit !== null
                ? (new \DateTimeImmutable($visit['last'], new \DateTimeZone('UTC')))->setTimezone($tz)->format('d.m.Y')
                : null;
            return $data;
        }, $places);
    }
}
