<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Checkin;
use App\Repository\CheckinRepository;
use App\Service\Osm\Geo;

/**
 * Validates and stores check-ins. Shared by the web form, the API and the importer.
 */
final class CheckinService
{
    public const MAX_NAME = 200;
    public const MAX_NOTE = 5000;

    public function __construct(
        private CheckinRepository $checkins,
        private string $defaultTimezone,
    ) {
    }

    /**
     * Normalises raw input. Accepted keys: lat, lon, place_name, osm_type, osm_id, note,
     * timezone, checked_in_at (any strtotime/ISO-8601 string or unix timestamp; default now).
     *
     * @param array<string, mixed> $input
     * @return array{data: ?array{lat: float, lon: float, place_name: string, osm_type: ?string, osm_id: ?int,
     *               checked_in_at: string, timezone: string, note: ?string, slug: string}, errors: list<string>}
     */
    public function validate(array $input, ?\DateTimeImmutable $now = null): array
    {
        $errors = [];

        $lat = filter_var($input['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lon = filter_var($input['lon'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lon === false || !Geo::validCoordinates($lat, $lon)) {
            $errors[] = 'Ungültige Koordinaten.';
        }

        $name = trim((string) ($input['place_name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Bitte einen Ortsnamen angeben.';
        } elseif (mb_strlen($name) > self::MAX_NAME) {
            $errors[] = 'Der Ortsname ist zu lang.';
        }

        $osmType = trim((string) ($input['osm_type'] ?? ''));
        $osmIdRaw = $input['osm_id'] ?? '';
        $osmType = $osmType === '' ? null : $osmType;
        $osmId = $osmIdRaw === '' ? null : filter_var($osmIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($osmType !== null || $osmId !== null) {
            if (!in_array($osmType, ['node', 'way', 'relation'], true) || !is_int($osmId)) {
                $errors[] = 'Ungültige OSM-Referenz.';
                $osmId = null;
            }
        }

        $note = trim((string) ($input['note'] ?? ''));
        if (mb_strlen($note) > self::MAX_NOTE) {
            $errors[] = 'Die Notiz ist zu lang.';
        }

        $timezone = self::normalizeTimezone(trim((string) ($input['timezone'] ?? ''))) ?? $this->defaultTimezone;

        $time = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $rawTime = $input['checked_in_at'] ?? null;
        if ($rawTime !== null && $rawTime !== '') {
            try {
                $time = is_numeric($rawTime)
                    ? (new \DateTimeImmutable('@' . (int) $rawTime))
                    // Strings without offset are read in the check-in's timezone.
                    : new \DateTimeImmutable((string) $rawTime, new \DateTimeZone($timezone));
            } catch (\Exception) {
                $errors[] = 'Ungültiger Zeitpunkt.';
            }
        }

        if ($errors !== []) {
            return ['data' => null, 'errors' => $errors];
        }

        return ['data' => [
            'lat' => round((float) $lat, 7),
            'lon' => round((float) $lon, 7),
            'place_name' => $name,
            'osm_type' => $osmType,
            'osm_id' => $osmId === null ? null : (int) $osmId,
            'checked_in_at' => $time->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'timezone' => $timezone,
            'note' => $note === '' ? null : $note,
            'slug' => Slugger::slugify($name),
        ], 'errors' => []];
    }

    /**
     * An IANA zone ("Europe/Berlin") or a fixed UTC offset ("+05:30") – the latter for
     * imports that only know the offset. Returns null if neither.
     */
    public static function normalizeTimezone(string $timezone): ?string
    {
        if (in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            return $timezone;
        }
        if (preg_match('/^[+-](0\d|1[0-4]):[0-5]\d$/', $timezone)) {
            return $timezone;
        }
        return null;
    }

    /**
     * @param array<string, mixed> $input
     * @return Checkin|list<string> the new check-in or validation errors
     */
    public function create(array $input, string $source, ?string $externalId = null): Checkin|array
    {
        $result = $this->validate($input);
        if ($result['data'] === null) {
            return $result['errors'];
        }
        $id = $this->checkins->create($result['data'] + ['source' => $source, 'external_id' => $externalId]);
        return $this->checkins->find($id) ?? throw new \RuntimeException('Check-in vanished after insert.');
    }

    /**
     * Updates name, note, time and timezone. Location and OSM reference stay unless given.
     *
     * @param array<string, mixed> $input
     * @return Checkin|list<string> the updated check-in or validation errors
     */
    public function update(Checkin $checkin, array $input): Checkin|array
    {
        $result = $this->validate($input + [
            'lat' => $checkin->lat,
            'lon' => $checkin->lon,
            'osm_type' => $checkin->osmType,
            'osm_id' => $checkin->osmId,
        ]);
        if ($result['data'] === null) {
            return $result['errors'];
        }
        $this->checkins->update($checkin->id, $result['data']);
        return $this->checkins->find($checkin->id) ?? throw new \RuntimeException('Check-in vanished after update.');
    }
}
