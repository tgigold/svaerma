# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden hier dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
und das Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [Unreleased]

## [0.2.0] - 2026-10-29

### Hinzugefügt
- Check-in-Seite: Standort per Browser-Geolocation, Orte im Umkreis über Overpass (sortiert nach Entfernung), Umkreis erweitern, Namenssuche über Photon oder Nominatim, eigener Ort mit freiem Namen, optionale Notiz.
- „Zuletzt hier am …“ und Besuchsanzahl für OSM-Orte in der Ergebnisliste.
- Datenmodell für Check-ins (Migration `002_checkins`) mit OSM-Referenz, UTC-Zeit und Zeitzone, Slug, Quelle und `external_id` für spätere Importe.
- Cache-Tabelle `osm_cache` mit TTL. User-Agent mit Kontakt-Mail, Nominatim gedrosselt auf 1 Anfrage pro Sekunde, Endpunkte per Config austauschbar.
- Einstellungsseite mit Suchradius (Standard: 150 m).
- Tests für die Overpass-, Photon- und Nominatim-Auswertung, Cache, Slugs und Check-in-Validierung.

### Geändert
- JSON-Anfragen ohne Login bekommen 401 statt einer Weiterleitung, bei ausstehender Migration 503.

## [0.1.0] - 2026-10-29

### Hinzugefügt
- Projektgerüst ohne Framework: Front Controller, Router mit Platzhaltern, Request/Response, eigener PSR-4-Autoloader.
- Konfiguration mit Defaults in `src/Config.php` und `config/config.php` für lokale Werte.
- Web-Setup unter `/setup`, geschützt durch einen Einmal-Code in `data/setup-token.txt`. Sperrt sich nach dem Lauf selbst. CLI-Pendant: `bin/setup.php`.
- Migrator mit Tabelle `schema_migrations`, einer Transaktion pro Migration und automatischem Backup (`VACUUM INTO`) vor jedem Lauf. Ausführbar per Admin-Button oder `bin/migrate.php`.
- Bei ausstehenden Migrationen leitet die App nach `/admin` weiter.
- Login für einen Nutzer mit Passwort-Hash, CSRF-Schutz, Rate-Limiting bei Fehlversuchen und gehärteten Session-Cookies.
- Admin-Seite mit Systeminfos, Migrationsstatus und manuellem Backup.
- Sicherheits-Header (CSP, HSTS, X-Frame-Options), HTTPS-Pflicht, `.htaccess`-Sperren für alle Verzeichnisse außerhalb von `public/` (auch im Fallback-Modus).
- Oberfläche mit Bootstrap 5.3 und Bootstrap Icons per CDN (mit SRI), automatischer Dark Mode.
- GitHub Action: Lint, PHPUnit und PHPStan, danach Deploy per FTPS bei Push auf `main`.
- Tests für Migrator, Router und Auth.

[Unreleased]: https://github.com/tgigold/svaerma/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/tgigold/svaerma/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/tgigold/svaerma/releases/tag/v0.1.0
