<?php

/*
 * Copy to config/config.php (setup does this for you) and adjust.
 * Every key is optional – missing keys fall back to the defaults in src/Config.php.
 */

return [
    // 'prod' hides error details, 'dev' shows them and allows plain HTTP.
    'env' => 'prod',

    // Public base URL without trailing slash, used for absolute links (API, export).
    'base_url' => 'https://checkin.gigold.me',

    // Contact address sent in the User-Agent to Overpass/Photon/Nominatim (fair use).
    'contact_email' => 'you@example.com',

    // Default timezone for display and for check-ins without a timezone.
    'timezone' => 'Europe/Berlin',

    // OpenStreetMap endpoints – swap for your own instances if you like.
    'osm' => [
        'overpass_url' => 'https://overpass-api.de/api/interpreter',
        'overpass_fallback_urls' => ['https://overpass.private.coffee/api/interpreter'], // [] = no fallback
        'photon_url' => 'https://photon.komoot.io/api/',
        'nominatim_url' => 'https://nominatim.openstreetmap.org/search',
        'search_provider' => 'photon', // 'photon' or 'nominatim'
        'tile_url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        'cache_ttl' => 86400, // seconds
        'timeout' => 25,      // HTTP timeout in seconds
    ],
];
