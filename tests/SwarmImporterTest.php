<?php

declare(strict_types=1);

namespace App\Tests;

use App\Model\Checkin;
use App\Repository\CheckinRepository;
use App\Service\CheckinService;
use App\Service\Exporter;
use App\Service\SwarmImporter;

final class SwarmImporterTest extends TestCase
{
    private CheckinRepository $repo;
    private SwarmImporter $importer;

    protected function setUp(): void
    {
        $this->repo = new CheckinRepository($this->migratedPdo());
        $this->importer = new SwarmImporter($this->repo, new CheckinService($this->repo, 'Europe/Berlin'), 'Europe/Berlin');
    }

    /** @return array<string, string> */
    private function files(): array
    {
        return ['checkins1.json' => (string) file_get_contents(__DIR__ . '/fixtures/sample-4sq.json')];
    }

    public function testDryRunReportsWithoutSaving(): void
    {
        $report = $this->importer->import($this->files(), true);

        self::assertTrue($report['dry_run']);
        self::assertSame(8, $report['total']);
        self::assertSame(5, $report['imported']);
        self::assertSame(1, $report['duplicates'], 'Same Swarm id twice in one file');
        self::assertSame(['aaa000000000000000000006', 'aaa000000000000000000007'], array_column($report['skipped'], 'id'));
        self::assertStringContainsString('shout', $report['skipped'][0]['reason']);
        self::assertSame('Keine Koordinaten.', $report['skipped'][1]['reason']);
        self::assertSame(0, $this->repo->count(), 'Dry run stores nothing');
    }

    public function testImportStoresAndIsIdempotent(): void
    {
        $first = $this->importer->import($this->files(), false);
        self::assertSame(5, $first['imported']);
        self::assertSame(5, $this->repo->count());

        $second = $this->importer->import($this->files(), false);
        self::assertSame(0, $second['imported']);
        self::assertSame(6, $second['duplicates'], 'Everything already imported is recognised');
        self::assertSame(5, $this->repo->count());
    }

    public function testMappedFields(): void
    {
        $this->importer->import($this->files(), false);
        $all = array_reverse($this->repo->page(50, 0)); // oldest first
        $breakfast = $all[0];

        self::assertSame('Zuhause', $breakfast->placeName);
        self::assertSame('swarm', $breakfast->source);
        self::assertSame('aaa000000000000000000003', $breakfast->externalId);
        self::assertSame('foursquare:venue0000000000000000001', $breakfast->venueRef);
        self::assertSame('Frühstück', $breakfast->note);
        self::assertSame('2010-01-06 09:31:29', $breakfast->checkedInAt->format('Y-m-d H:i:s'), 'createdAt is UTC');
        self::assertSame('Europe/Berlin', $breakfast->timezone, 'Offset matches the default zone in winter');
        self::assertSame('10:31', $breakfast->localTime()->format('H:i'));
        self::assertNull($breakfast->osmType);

        $summer = $this->byName($all, 'Biergarten');
        self::assertSame('Europe/Berlin', $summer->timezone, '+120 matches Berlin in summer');
        self::assertSame('12:00', $summer->localTime()->format('H:i'));

        $india = $this->byName($all, 'Chai-Stand');
        self::assertSame('+05:30', $india->timezone, 'Other offsets are kept as fixed offset');
        self::assertSame('12:00', $india->localTime()->format('H:i'));
    }

    public function testVenueGroupsVisits(): void
    {
        $this->importer->import($this->files(), false);
        self::assertCount(2, $this->repo->atVenue('foursquare:venue0000000000000000001'));
    }

    public function testShareTokenIsNotStored(): void
    {
        $this->importer->import($this->files(), false);
        $export = json_encode((new Exporter($this->repo, 'https://x.test'))->json());
        self::assertStringNotContainsString('SECRET', (string) $export);
    }

    public function testInvalidFileIsReported(): void
    {
        $report = $this->importer->import(['kaputt.json' => '{"foo": 1}'], true);
        self::assertSame(0, $report['total']);
        self::assertSame('kaputt.json', $report['skipped'][0]['file']);
    }

    public function testBareListVariantAndUnixTimestamps(): void
    {
        $json = (string) json_encode([[
            'id' => 'bbb1', 'createdAt' => 1262791954, 'timeZoneOffset' => 60,
            'venue' => ['id' => 'v1', 'name' => 'Ort'], 'lat' => 51.0, 'lng' => 12.0,
        ]]);
        $report = $this->importer->import(['x.json' => $json], false);
        self::assertSame(1, $report['imported']);
        self::assertSame('2010-01-06 15:32:34', $this->repo->page(1, 0)[0]->checkedInAt->format('Y-m-d H:i:s'));
    }

    public function testExportFormats(): void
    {
        $this->importer->import($this->files(), false);
        $exporter = new Exporter($this->repo, 'https://x.test');

        $json = $exporter->json();
        self::assertSame(5, $json['count']);
        self::assertSame('Zuhause', $json['checkins'][0]['place_name'], 'Oldest first');

        $geo = $exporter->geoJson();
        self::assertSame('FeatureCollection', $geo['type']);
        self::assertCount(5, $geo['features']);
        self::assertSame([12.1397, 51.4228], $geo['features'][0]['geometry']['coordinates'], '[lon, lat]');
        self::assertArrayNotHasKey('lat', $geo['features'][0]['properties']);
        self::assertStringStartsWith('https://x.test/checkin/', $geo['features'][0]['properties']['url']);
    }

    /** @param list<Checkin> $list */
    private function byName(array $list, string $name): Checkin
    {
        foreach ($list as $c) {
            if ($c->placeName === $name) {
                return $c;
            }
        }
        self::fail("No check-in named $name");
    }
}
