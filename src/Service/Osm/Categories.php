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
            'doctors' => 'Arztpraxis', 'dentist' => 'Zahnarzt', 'clinic' => 'Klinik', 'veterinary' => 'Tierarzt',
            'bank' => 'Bank', 'post_office' => 'Post', 'fuel' => 'Tankstelle', 'car_wash' => 'Waschanlage',
            'place_of_worship' => 'Gotteshaus', 'townhall' => 'Rathaus', 'courthouse' => 'Gericht', 'parking' => 'Parkplatz',
            'nightclub' => 'Club', 'casino' => 'Spielbank', 'marketplace' => 'Markt', 'community_centre' => 'Gemeindezentrum',
            'arts_centre' => 'Kulturzentrum', 'events_venue' => 'Veranstaltungsort', 'conference_centre' => 'Kongresszentrum',
            'coworking_space' => 'Coworking', 'police' => 'Polizei', 'fire_station' => 'Feuerwehr', 'bus_station' => 'Busbahnhof',
            'ferry_terminal' => 'Fähranleger', 'social_facility' => 'Soziale Einrichtung', 'music_school' => 'Musikschule',
            'driving_school' => 'Fahrschule', 'language_school' => 'Sprachschule', 'childcare' => 'Kinderbetreuung',
            'nursing_home' => 'Pflegeheim', 'grave_yard' => 'Friedhof', 'shelter' => 'Unterstand', 'food_court' => 'Food Court',
            'boat_rental' => 'Bootsverleih', 'bicycle_rental' => 'Fahrradverleih', 'car_rental' => 'Autovermietung',
            'studio' => 'Studio', 'public_bath' => 'Bad', 'stripclub' => 'Stripclub', 'prison' => 'Gefängnis',
        ],
        'shop' => [
            'supermarket' => 'Supermarkt', 'bakery' => 'Bäckerei', 'butcher' => 'Metzgerei', 'books' => 'Buchhandlung',
            'clothes' => 'Bekleidung', 'shoes' => 'Schuhe', 'convenience' => 'Laden', 'kiosk' => 'Kiosk', 'hairdresser' => 'Friseur',
            'beauty' => 'Kosmetik', 'optician' => 'Optiker', 'jewelry' => 'Schmuck', 'mall' => 'Einkaufszentrum',
            'department_store' => 'Kaufhaus', 'chemist' => 'Drogerie', 'electronics' => 'Elektronik', 'mobile_phone' => 'Handyladen',
            'computer' => 'Computerladen', 'hardware' => 'Baumarkt', 'doityourself' => 'Baumarkt', 'garden_centre' => 'Gartencenter',
            'furniture' => 'Möbel', 'bicycle' => 'Fahrradladen', 'car' => 'Autohaus', 'car_repair' => 'Autowerkstatt',
            'florist' => 'Blumenladen', 'gift' => 'Geschenke', 'toys' => 'Spielwaren', 'sports' => 'Sportgeschäft',
            'outdoor' => 'Outdoor', 'wine' => 'Weinhandlung', 'beverages' => 'Getränkemarkt', 'alcohol' => 'Spirituosen',
            'coffee' => 'Kaffee', 'tea' => 'Tee', 'deli' => 'Feinkost', 'greengrocer' => 'Obst & Gemüse', 'farm' => 'Hofladen',
            'confectionery' => 'Süßwaren', 'pastry' => 'Konditorei', 'pet' => 'Zoohandlung', 'tobacco' => 'Tabak',
            'newsagent' => 'Zeitschriften', 'stationery' => 'Schreibwaren', 'laundry' => 'Wäscherei', 'dry_cleaning' => 'Reinigung',
            'second_hand' => 'Second Hand', 'variety_store' => 'Gemischtwaren', 'music' => 'Musikladen', 'photo' => 'Fotoladen',
            'travel_agency' => 'Reisebüro', 'tattoo' => 'Tattoo-Studio', 'massage' => 'Massage', 'cosmetics' => 'Kosmetik',
            'art' => 'Kunsthandlung', 'antiques' => 'Antiquitäten', 'bag' => 'Taschen', 'interior_decoration' => 'Einrichtung',
        ],
        'tourism' => [
            'hotel' => 'Hotel', 'hostel' => 'Hostel', 'guest_house' => 'Pension', 'motel' => 'Motel', 'apartment' => 'Ferienwohnung',
            'chalet' => 'Ferienhaus', 'museum' => 'Museum', 'gallery' => 'Galerie', 'attraction' => 'Sehenswürdigkeit',
            'viewpoint' => 'Aussichtspunkt', 'zoo' => 'Zoo', 'aquarium' => 'Aquarium', 'theme_park' => 'Freizeitpark',
            'artwork' => 'Kunstwerk', 'information' => 'Info', 'camp_site' => 'Campingplatz', 'caravan_site' => 'Stellplatz',
            'picnic_site' => 'Picknickplatz',
        ],
        'leisure' => [
            'park' => 'Park', 'playground' => 'Spielplatz', 'stadium' => 'Stadion', 'sports_centre' => 'Sportzentrum',
            'sports_hall' => 'Sporthalle', 'fitness_centre' => 'Fitnessstudio', 'swimming_pool' => 'Schwimmbad', 'water_park' => 'Spaßbad',
            'beach_resort' => 'Strandbad', 'sauna' => 'Sauna', 'garden' => 'Garten', 'pitch' => 'Sportplatz', 'track' => 'Laufbahn',
            'golf_course' => 'Golfplatz', 'miniature_golf' => 'Minigolf', 'ice_rink' => 'Eisbahn', 'bowling_alley' => 'Bowling',
            'escape_game' => 'Escape Room', 'amusement_arcade' => 'Spielhalle', 'dance' => 'Tanzlokal', 'marina' => 'Hafen',
            'nature_reserve' => 'Naturschutzgebiet', 'dog_park' => 'Hundewiese', 'horse_riding' => 'Reitanlage',
            'outdoor_seating' => 'Außenbereich', 'common' => 'Grünfläche', 'resort' => 'Resort',
        ],
        'historic' => [
            'monument' => 'Denkmal', 'memorial' => 'Gedenkstätte', 'castle' => 'Burg', 'manor' => 'Herrenhaus', 'palace' => 'Palast',
            'ruins' => 'Ruine', 'church' => 'Kirche', 'monastery' => 'Kloster', 'fort' => 'Festung', 'city_gate' => 'Stadttor',
            'building' => 'Historisches Gebäude', 'archaeological_site' => 'Ausgrabungsstätte', 'wayside_cross' => 'Wegkreuz',
            'wayside_shrine' => 'Bildstock', 'tomb' => 'Grabmal', 'boundary_stone' => 'Grenzstein', 'mine' => 'Bergwerk',
            'mill' => 'Mühle', 'tower' => 'Turm', 'ship' => 'Historisches Schiff', 'locomotive' => 'Historische Lok',
        ],
        'craft' => [
            'window_construction' => 'Fensterbau', 'carpenter' => 'Tischlerei', 'joiner' => 'Tischlerei', 'electrician' => 'Elektriker',
            'plumber' => 'Klempner', 'hvac' => 'Heizung & Sanitär', 'painter' => 'Malerbetrieb', 'roofer' => 'Dachdecker',
            'tiler' => 'Fliesenleger', 'glaziery' => 'Glaserei', 'metal_construction' => 'Metallbau', 'locksmith' => 'Schlüsseldienst',
            'brewery' => 'Brauerei', 'winery' => 'Weingut', 'distillery' => 'Brennerei', 'confectionery' => 'Konditorei',
            'photographer' => 'Fotograf', 'tailor' => 'Schneiderei', 'shoemaker' => 'Schuhmacher', 'stonemason' => 'Steinmetz',
            'gardener' => 'Gärtnerei', 'beekeeper' => 'Imkerei', 'builder' => 'Baufirma', 'caterer' => 'Catering',
        ],
        'office' => [
            'company' => 'Firma', 'government' => 'Behörde', 'insurance' => 'Versicherung', 'lawyer' => 'Kanzlei',
            'estate_agent' => 'Immobilien', 'accountant' => 'Steuerberatung', 'tax_advisor' => 'Steuerberatung',
            'architect' => 'Architekturbüro', 'it' => 'IT-Firma', 'association' => 'Verein', 'ngo' => 'Organisation',
            'employment_agency' => 'Arbeitsagentur', 'notary' => 'Notar', 'coworking' => 'Coworking', 'newspaper' => 'Zeitung',
            'educational_institution' => 'Bildungseinrichtung', 'religion' => 'Religiöse Einrichtung', 'political_party' => 'Partei',
        ],
        'healthcare' => [
            'doctor' => 'Arztpraxis', 'dentist' => 'Zahnarzt', 'hospital' => 'Krankenhaus', 'pharmacy' => 'Apotheke',
            'physiotherapist' => 'Physiotherapie', 'psychotherapist' => 'Psychotherapie', 'clinic' => 'Klinik',
            'alternative' => 'Naturheilkunde', 'laboratory' => 'Labor', 'optometrist' => 'Optiker',
        ],
        'sport' => [
            'soccer' => 'Fußball', 'tennis' => 'Tennis', 'swimming' => 'Schwimmen', 'climbing' => 'Klettern', 'golf' => 'Golf',
            'fitness' => 'Fitness', 'equestrian' => 'Reitsport', 'shooting' => 'Schießstand', 'bowling' => 'Bowling',
            'basketball' => 'Basketball', 'volleyball' => 'Volleyball', 'skateboard' => 'Skatepark', 'multi' => 'Sportanlage',
        ],
        'building' => [
            'church' => 'Kirche', 'cathedral' => 'Dom', 'chapel' => 'Kapelle', 'mosque' => 'Moschee', 'synagogue' => 'Synagoge',
            'temple' => 'Tempel', 'train_station' => 'Bahnhof', 'castle' => 'Schloss', 'stadium' => 'Stadion', 'hotel' => 'Hotel',
            'school' => 'Schule', 'university' => 'Universität', 'hospital' => 'Krankenhaus', 'public' => 'Öffentliches Gebäude',
            'civic' => 'Öffentliches Gebäude', 'museum' => 'Museum', 'farm' => 'Bauernhof', 'barn' => 'Scheune', 'tower' => 'Turm',
        ],
        'railway' => ['station' => 'Bahnhof', 'halt' => 'Haltepunkt', 'tram_stop' => 'Tramhaltestelle', 'subway_entrance' => 'U-Bahn-Eingang'],
        'public_transport' => ['station' => 'Station', 'stop_area' => 'Haltestelle'],
        'aeroway' => ['aerodrome' => 'Flughafen', 'terminal' => 'Terminal', 'gate' => 'Gate', 'helipad' => 'Hubschrauberlandeplatz'],
        'natural' => [
            'peak' => 'Gipfel', 'beach' => 'Strand', 'spring' => 'Quelle', 'cave_entrance' => 'Höhle', 'volcano' => 'Vulkan',
            'bay' => 'Bucht', 'glacier' => 'Gletscher', 'saddle' => 'Pass', 'hill' => 'Hügel', 'waterfall' => 'Wasserfall',
        ],
        'man_made' => [
            'tower' => 'Turm', 'lighthouse' => 'Leuchtturm', 'windmill' => 'Windmühle', 'watermill' => 'Wassermühle',
            'bridge' => 'Brücke', 'pier' => 'Seebrücke', 'water_tower' => 'Wasserturm', 'observatory' => 'Sternwarte',
            'works' => 'Werk', 'chimney' => 'Schornstein', 'adit' => 'Stollen', 'monitoring_station' => 'Messstation',
        ],
    ];

    /**
     * Whether a tag set describes a place worth checking in to.
     *
     * @param array<string, string> $tags
     */
    public static function matches(array $tags): bool
    {
        foreach (self::KEYS as $key) {
            $value = $tags[$key] ?? null;
            if ($value === null) {
                continue;
            }
            if (!isset(self::EXCLUDED[$key]) || !preg_match('/^(' . self::EXCLUDED[$key] . ')$/', $value)) {
                return true;
            }
        }
        return false;
    }

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
