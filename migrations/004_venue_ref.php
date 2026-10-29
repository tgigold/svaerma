<?php

declare(strict_types=1);

// Reference to a venue in another system (e.g. "foursquare:4b44…" from the Swarm import),
// so repeated visits to places without an OSM reference can be grouped.
return static function (PDO $pdo): void {
    $pdo->exec('ALTER TABLE checkins ADD COLUMN venue_ref TEXT NULL');
    $pdo->exec('CREATE INDEX idx_checkins_venue ON checkins (venue_ref)');
};
