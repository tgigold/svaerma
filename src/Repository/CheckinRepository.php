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

    /** @return list<Checkin> newest first */
    public function page(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checkins ORDER BY checked_in_at DESC, id DESC LIMIT ? OFFSET ?');
        $stmt->execute([$limit, $offset]);
        return array_map(Checkin::fromRow(...), $stmt->fetchAll());
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM checkins')->fetchColumn();
    }

    /**
     * Compact rows for the map.
     *
     * @return list<array{id: int, lat: float, lon: float, place_name: string, slug: string, checked_in_at: string}>
     */
    public function allForMap(): array
    {
        $rows = $this->pdo->query('SELECT id, lat, lon, place_name, slug, checked_in_at FROM checkins ORDER BY checked_in_at')->fetchAll();
        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'lat' => (float) $r['lat'],
            'lon' => (float) $r['lon'],
            'place_name' => (string) $r['place_name'],
            'slug' => (string) $r['slug'],
            'checked_in_at' => (string) $r['checked_in_at'],
        ], $rows);
    }

    /** @return list<Checkin> All check-ins at the same OSM object, newest first */
    public function atPlace(string $osmType, int $osmId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checkins WHERE osm_type = ? AND osm_id = ? ORDER BY checked_in_at DESC');
        $stmt->execute([$osmType, $osmId]);
        return array_map(Checkin::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @param array{place_name: string, checked_in_at: string, timezone: string, note: ?string, slug: string,
     *              lat?: float, lon?: float, osm_type?: ?string, osm_id?: ?int} $data
     */
    public function update(int $id, array $data): void
    {
        $allowed = ['lat', 'lon', 'place_name', 'osm_type', 'osm_id', 'checked_in_at', 'timezone', 'note', 'slug'];
        $data = array_intersect_key($data, array_flip($allowed));
        $sets = array_map(static fn (string $col): string => "$col = :$col", array_keys($data));
        $stmt = $this->pdo->prepare('UPDATE checkins SET ' . implode(', ', $sets) . ', updated_at = :updated_at WHERE id = :id');
        $stmt->execute($data + ['updated_at' => gmdate('Y-m-d H:i:s'), 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM checkins WHERE id = ?')->execute([$id]);
    }
}
