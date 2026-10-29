<?php

declare(strict_types=1);

namespace App\Service\Osm;

/**
 * Which OSM tags count as "places you can check in to", and German labels for common values.
 */
final class Categories
{
    /** Tag keys queried by the nearby search. */
    public const KEYS = ['amenity', 'shop', 'tourism', 'leisure', 'historic', 'craft', 'office', 'healthcare', 'sport', 'railway', 'public_transport', 'aeroway', 'building', 'natural', 'man_made'];

    /** Values that are named but not places anybody checks in to. */
    public const EXCLUDED = [
        'amenity' => 'bench|waste_basket|parking_space|vending_machine|bicycle_parking|recycling|post_box|telephone|clock|drinking_water|toilets|charging_station|grit_bin',
        'railway' => 'rail|tram|subway|light_rail|abandoned|disused|switch|signal|level_crossing|crossing|buffer_stop|platform_edge',
        'building' => 'yes|house|residential|apartments|garage|garages|detached|terrace|semidetached_house|roof|shed|industrial|commercial|retail|office',
        'natural' => 'tree|tree_row|scrub|wood|grassland|water|wetland|coastline|cliff|rock|stone',
        'man_made' => 'pipeline|street_cabinet|manhole|surveillance|pole|mast|flagpole|antenna|cutline|embankment|dyke|breakwater|groyne',
        'public_transport' => 'platform|stop_position',
    ];

    private const LABELS = [
        'amenity' => [
            'cafe' => 'Café', 'restaurant' => 'Restaurant', 'fast_food' => 'Imbiss', 'bar' => 'Bar', 'pub' => 'Kneipe',
            'biergarten' => 'Biergarten', 'ice_cream' => 'Eisdiele', 'bakery' => 'Bäckerei', 'cinema' => 'Kino',
            'theatre' => 'Theater', 'library' => 'Bibliothek', 'school' => 'Schule', 'university' => 'Universität',
            'college' => 'Hochschule', 'kindergarten' => 'Kindergarten', 'hospital' => 'Krankenhaus', 'pharmacy' => 'Apotheke',
            'doctors' => 'Arztpraxis', 'dentist' => 'Zahnarzt', 'bank' => 'Bank', 'post_office' => 'Post', 'fuel' => 'Tankstelle',
            'place_of_worship' => 'Gotteshaus', 'townhall' => 'Rathaus', 'parking' => 'Parkplatz', 'nightclub' => 'Club',
            'marketplace' => 'Markt', 'community_centre' => 'Gemeindezentrum', 'arts_centre' => 'Kulturzentrum',
            'coworking_space' => 'Coworking', 'police' => 'Polizei', 'fire_station' => 'Feuerwehr', 'bus_station' => 'Busbahnhof',
        ],
        'shop' => [
            'supermarket' => 'Supermarkt', 'bakery' => 'Bäckerei', 'butcher' => 'Metzgerei', 'books' => 'Buchhandlung',
            'clothes' => 'Bekleidung', 'convenience' => 'Laden', 'kiosk' => 'Kiosk', 'hairdresser' => 'Friseur',
            'mall' => 'Einkaufszentrum', 'department_store' => 'Kaufhaus', 'chemist' => 'Drogerie', 'electronics' => 'Elektronik',
            'hardware' => 'Baumarkt', 'doityourself' => 'Baumarkt', 'bicycle' => 'Fahrradladen', 'florist' => 'Blumenladen',
        ],
        'tourism' => [
            'hotel' => 'Hotel', 'hostel' => 'Hostel', 'guest_house' => 'Pension', 'museum' => 'Museum', 'gallery' => 'Galerie',
            'attraction' => 'Sehenswürdigkeit', 'viewpoint' => 'Aussichtspunkt', 'zoo' => 'Zoo', 'theme_park' => 'Freizeitpark',
            'artwork' => 'Kunstwerk', 'information' => 'Info', 'camp_site' => 'Campingplatz', 'picnic_site' => 'Picknickplatz',
        ],
        'leisure' => [
            'park' => 'Park', 'playground' => 'Spielplatz', 'stadium' => 'Stadion', 'sports_centre' => 'Sportzentrum',
            'fitness_centre' => 'Fitnessstudio', 'swimming_pool' => 'Schwimmbad', 'garden' => 'Garten', 'pitch' => 'Sportplatz',
            'marina' => 'Hafen', 'nature_reserve' => 'Naturschutzgebiet', 'water_park' => 'Spaßbad',
        ],
        'historic' => ['monument' => 'Denkmal', 'memorial' => 'Gedenkstätte', 'castle' => 'Burg', 'ruins' => 'Ruine', 'church' => 'Kirche'],
        'railway' => ['station' => 'Bahnhof', 'halt' => 'Haltepunkt', 'tram_stop' => 'Tramhaltestelle', 'subway_entrance' => 'U-Bahn-Eingang'],
        'public_transport' => ['station' => 'Station'],
        'aeroway' => ['aerodrome' => 'Flughafen', 'terminal' => 'Terminal', 'gate' => 'Gate'],
        'natural' => ['peak' => 'Gipfel', 'beach' => 'Strand', 'spring' => 'Quelle', 'cave_entrance' => 'Höhle'],
    ];

    /**
     * Human-readable category for an OSM tag set, e.g. "Café" or "Shop: Tea".
     *
     * @param array<string, string> $tags
     */
    public static function label(array $tags): ?string
    {
        foreach (self::KEYS as $key) {
            $value = $tags[$key] ?? null;
            if ($value === null || $value === 'yes') {
                continue;
            }
            return self::LABELS[$key][$value] ?? ucfirst(str_replace('_', ' ', $value));
        }
        return null;
    }
}
