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
}
