<?php

declare(strict_types=1);

namespace App\Tests;

use App\Model\Checkin;
use App\Repository\CheckinRepository;
use App\Service\CheckinService;

final class CheckinTest extends TestCase
{
    private function service(?CheckinRepository &$repo = null): CheckinService
    {
        $repo = new CheckinRepository($this->migratedPdo());
        return new CheckinService($repo, 'Europe/Berlin');
    }

    public function testCreatesCheckinWithOsmReference(): void
    {
        $checkin = $this->service($repo)->create([
            'lat' => '52.52', 'lon' => '13.405', 'place_name' => '  Café Müller ', 'osm_type' => 'node', 'osm_id' => '1001',
            'note' => 'Guter Flat White', 'timezone' => 'Europe/Berlin',
        ], 'web');

        self::assertInstanceOf(Checkin::class, $checkin);
        self::assertSame('Café Müller', $checkin->placeName);
        self::assertSame('cafe-mueller', $checkin->slug);
        self::assertSame('node', $checkin->osmType);
        self::assertSame(1001, $checkin->osmId);
        self::assertSame('web', $checkin->source);
        self::assertSame('/checkin/' . $checkin->id . '-cafe-mueller', $checkin->path());
        self::assertEqualsWithDelta(time(), $checkin->checkedInAt->getTimestamp(), 5);
    }

    public function testManualPlaceWithoutOsm(): void
    {
        $checkin = $this->service()->create(['lat' => 1, 'lon' => 2, 'place_name' => 'Garten', 'osm_type' => '', 'osm_id' => ''], 'web');
        self::assertInstanceOf(Checkin::class, $checkin);
        self::assertNull($checkin->osmType);
        self::assertNull($checkin->osmId);
        self::assertNull($checkin->note);
    }

    public function testValidationErrors(): void
    {
        $errors = $this->service()->create(['lat' => '95', 'lon' => 'x', 'place_name' => '', 'osm_type' => 'node', 'osm_id' => 'abc'], 'web');
        self::assertIsArray($errors);
        self::assertCount(3, $errors);
    }

    public function testTimeIsStoredInUtcAndTimezoneKept(): void
    {
        $result = $this->service()->validate([
            'lat' => 1, 'lon' => 1, 'place_name' => 'X', 'timezone' => 'America/New_York', 'checked_in_at' => '2024-07-01 12:00',
        ]);
        self::assertSame('2024-07-01 16:00:00', $result['data']['checked_in_at'] ?? null);
        self::assertSame('America/New_York', $result['data']['timezone'] ?? null);

        $result = $this->service()->validate(['lat' => 1, 'lon' => 1, 'place_name' => 'X', 'timezone' => 'Mars/Olympus', 'checked_in_at' => 1700000000]);
        self::assertSame('Europe/Berlin', $result['data']['timezone'] ?? null, 'Unknown zones fall back to the default');
        self::assertSame('2023-11-14 22:13:20', $result['data']['checked_in_at'] ?? null);
    }

    public function testLocalTimeUsesCheckinTimezone(): void
    {
        $checkin = $this->service()->create(['lat' => 1, 'lon' => 1, 'place_name' => 'X', 'timezone' => 'Asia/Tokyo', 'checked_in_at' => '2024-01-01T00:00:00Z'], 'web');
        self::assertInstanceOf(Checkin::class, $checkin);
        self::assertSame('2024-01-01 09:00', $checkin->localTime()->format('Y-m-d H:i'));
    }

    public function testVisitsForReturnsLastVisitAndCount(): void
    {
        $service = $this->service($repo);
        foreach (['2024-01-01 10:00', '2024-03-01 10:00'] as $t) {
            $service->create(['lat' => 1, 'lon' => 1, 'place_name' => 'A', 'osm_type' => 'node', 'osm_id' => 5, 'checked_in_at' => $t . 'Z'], 'web');
        }
        $service->create(['lat' => 1, 'lon' => 1, 'place_name' => 'B', 'osm_type' => 'way', 'osm_id' => 5, 'checked_in_at' => '2024-02-01Z'], 'web');

        $visits = $repo->visitsFor([['node', 5], ['way', 5], ['node', 99]]);
        self::assertSame(['last' => '2024-03-01 10:00:00', 'count' => 2], $visits['node/5']);
        self::assertSame(1, $visits['way/5']['count']);
        self::assertArrayNotHasKey('node/99', $visits);
        self::assertSame([], $repo->visitsFor([]));
    }

    public function testPaginationCountAndOrder(): void
    {
        $service = $this->service($repo);
        foreach (['2024-01-03', '2024-01-01', '2024-01-02'] as $i => $day) {
            $service->create(['lat' => 1, 'lon' => 1, 'place_name' => "P$i", 'checked_in_at' => $day . 'T12:00:00Z'], 'web');
        }
        self::assertSame(3, $repo->count());
        self::assertSame(['P0', 'P2'], array_map(fn (Checkin $c) => $c->placeName, $repo->page(2, 0)), 'Newest first');
        self::assertSame(['P1'], array_map(fn (Checkin $c) => $c->placeName, $repo->page(2, 2)));
        self::assertSame(['P1', 'P2', 'P0'], array_column($repo->allForMap(), 'place_name'), 'Map data is chronological');
    }

    public function testUpdateKeepsLocationAndRegeneratesSlug(): void
    {
        $service = $this->service($repo);
        $checkin = $service->create(['lat' => 52.5, 'lon' => 13.4, 'place_name' => 'Alt', 'osm_type' => 'node', 'osm_id' => 7], 'web');
        self::assertInstanceOf(Checkin::class, $checkin);

        $updated = $service->update($checkin, [
            'place_name' => 'Neuer Name', 'note' => 'Hallo', 'checked_in_at' => '2024-05-01T08:30', 'timezone' => 'Europe/Berlin',
        ]);
        self::assertInstanceOf(Checkin::class, $updated);
        self::assertSame('neuer-name', $updated->slug);
        self::assertSame('Hallo', $updated->note);
        self::assertSame(52.5, $updated->lat);
        self::assertSame(7, $updated->osmId);
        self::assertSame('2024-05-01 06:30:00', $updated->checkedInAt->format('Y-m-d H:i:s'), 'Local time converted to UTC');

        self::assertIsArray($service->update($updated, ['place_name' => '', 'checked_in_at' => 'kaputt']));
    }

    public function testDeleteAndAtPlace(): void
    {
        $service = $this->service($repo);
        $a = $service->create(['lat' => 1, 'lon' => 1, 'place_name' => 'A', 'osm_type' => 'way', 'osm_id' => 3, 'checked_in_at' => '2024-01-01Z'], 'web');
        $b = $service->create(['lat' => 1, 'lon' => 1, 'place_name' => 'A', 'osm_type' => 'way', 'osm_id' => 3, 'checked_in_at' => '2024-02-01Z'], 'web');
        self::assertInstanceOf(Checkin::class, $a);
        self::assertInstanceOf(Checkin::class, $b);

        self::assertSame([$b->id, $a->id], array_map(fn (Checkin $c) => $c->id, $repo->atPlace('way', 3)));
        $repo->delete($a->id);
        self::assertNull($repo->find($a->id));
        self::assertCount(1, $repo->atPlace('way', 3));
    }

    public function testCanonicalRedirectForWrongOrMissingSlug(): void
    {
        $checkin = $this->service()->create(['lat' => 1, 'lon' => 1, 'place_name' => 'Café XYZ'], 'web');
        self::assertInstanceOf(Checkin::class, $checkin);
        $canonical = '/checkin/' . $checkin->id . '-cafe-xyz';

        self::assertNull($checkin->canonicalRedirect('-cafe-xyz'));
        self::assertSame($canonical, $checkin->canonicalRedirect(''));
        self::assertSame($canonical, $checkin->canonicalRedirect('-alter-name'));
        self::assertSame($canonical, $checkin->canonicalRedirect('-CAFE-XYZ'));
    }

    public function testCheckinWithoutSlugHasPlainPath(): void
    {
        $checkin = $this->service()->create(['lat' => 1, 'lon' => 1, 'place_name' => '!!!'], 'web');
        self::assertInstanceOf(Checkin::class, $checkin);
        self::assertSame('/checkin/' . $checkin->id, $checkin->path());
        self::assertNull($checkin->canonicalRedirect(''));
        self::assertSame($checkin->path(), $checkin->canonicalRedirect('-x'));
    }
}
