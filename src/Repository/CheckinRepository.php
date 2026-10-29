<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Checkin;
use PDO;

final class CheckinRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array{lat: float, lon: float, place_name: string, osm_type: ?string, osm_id: ?int,
     *              checked_in_at: string, timezone: string, note: ?string, slug: string,
     *              source: string, external_id?: ?string} $data
     */
    public function create(array $data): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO checkins (lat, lon, place_name, osm_type, osm_id, checked_in_at, timezone, note, slug,
                                   source, external_id, created_at, updated_at)
             VALUES (:lat, :lon, :place_name, :osm_type, :osm_id, :checked_in_at, :timezone, :note, :slug,
                     :source, :external_id, :created_at, :updated_at)'
        );
        $stmt->execute([
            'lat' => $data['lat'],
            'lon' => $data['lon'],
            'place_name' => $data['place_name'],
            'osm_type' => $data['osm_type'],
            'osm_id' => $data['osm_id'],
            'checked_in_at' => $data['checked_in_at'],
            'timezone' => $data['timezone'],
            'note' => $data['note'],
            'slug' => $data['slug'],
            'source' => $data['source'],
            'external_id' => $data['external_id'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?Checkin
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checkins WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? Checkin::fromRow($row) : null;
    }

    /**
     * Most recent check-in time (UTC) per OSM object.
     *
     * @param list<array{0: string, 1: int}> $refs [osm_type, osm_id] pairs
     * @return array<string, array{last: string, count: int}> keyed by "type/id"
     */
    public function visitsFor(array $refs): array
    {
        if ($refs === []) {
            return [];
        }
        $conditions = [];
        $params = [];
        foreach ($refs as [$type, $id]) {
            $conditions[] = '(osm_type = ? AND osm_id = ?)';
            $params[] = $type;
            $params[] = $id;
        }
        $stmt = $this->pdo->prepare(
            'SELECT osm_type, osm_id, MAX(checked_in_at) AS last, COUNT(*) AS cnt FROM checkins
             WHERE ' . implode(' OR ', $conditions) . ' GROUP BY osm_type, osm_id'
        );
        $stmt->execute($params);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['osm_type'] . '/' . $row['osm_id']] = ['last' => (string) $row['last'], 'count' => (int) $row['cnt']];
        }
        return $result;
    }
}
