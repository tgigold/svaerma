# Check-in

Ein persönliches Check-in-Log als Swarm-Ersatz: Du checkst bewusst an Orten ein und baust dir so ein privates Archiv der Orte auf, an denen du warst. Es gibt kein Social, kein Hintergrund-Tracking und nur einen Nutzer. Die Ortsdaten kommen ausschließlich aus OpenStreetMap.

Live unter `https://checkin.gigold.me`.

## Inhalt

- [Anforderungen](#anforderungen)
- [Verzeichnisstruktur](#verzeichnisstruktur)
- [Installation](#installation)
- [Konfiguration](#konfiguration)
- [Einchecken und OpenStreetMap](#einchecken-und-openstreetmap)
- [Deployment und Updates](#deployment-und-updates)
- [Migrationen](#migrationen)
- [Backup](#backup)
- [Lokale Entwicklung](#lokale-entwicklung)
- [Ein neues Feature hinzufügen](#ein-neues-feature-hinzufügen)
- [Sicherheit](#sicherheit)

## Anforderungen

- PHP ≥ 8.3 (Zielsystem: 8.5) mit `pdo_sqlite`, `mbstring`, `intl`
- SQLite ≥ 3.27 (für `VACUUM INTO`, Zielsystem: 3.45)
- Apache mit `mod_rewrite` und `.htaccess` (`AllowOverride All`)
- HTTPS (die Geolocation im Browser funktioniert nur darüber)

Es gibt keine Laufzeit-Abhängigkeiten. Composer brauchst du nur für die Dev-Tools (PHPUnit, PHPStan), die App selbst läuft ohne `vendor/`. Bootstrap, Bootstrap Icons und Leaflet kommen per CDN (jsDelivr) mit Subresource Integrity.

## Verzeichnisstruktur

```
public/        Document Root: index.php (Front Controller), .htaccess, assets/
src/           App-Code im Namespace App\ (eigener PSR-4-Autoloader in src/bootstrap.php)
  Http/        Router, Request, Response, Session
  Controller/  ein Controller pro Bereich
  Repository/  sämtlicher SQL-Code
  Service/     Logik ohne HTTP-Bezug (Auth, Setup, …)
  Database/    Verbindung, Migrator, Backup
  routes.php   alle Routen an einem Ort
templates/     PHP-Templates (Ausgabe immer über e())
config/        config.example.php; die echte config.php wird beim Setup erzeugt (gitignored)
data/          SQLite-DB, Backups, Sessions, Logs (gitignored, per .htaccess gesperrt)
migrations/    nummerierte Migrationen 001_….php
bin/           CLI-Skripte (setup, migrate) für die lokale Nutzung
tests/         PHPUnit
```

## Installation

Die Domain muss auf `public/` zeigen, alles andere liegt eine Ebene darüber.

### Ohne SSH (der Normalfall hier)

1. Code deployen, entweder über die GitHub Action (siehe [Deployment](#deployment-und-updates)) oder per FTP hochladen.
2. `https://checkin.gigold.me/setup` aufrufen. Die App legt dabei einen Einmal-Code in `data/setup-token.txt` an.
3. Die Datei per FTP öffnen und den Code ins Formular übernehmen.
4. Passwort, Kontakt-Mail, Adresse und Zeitzone eintragen und auf **Einrichten** klicken.

Das Setup erzeugt `config/config.php`, legt die Datenbank an, führt alle Migrationen aus und speichert den Passwort-Hash. Sobald `config/config.php` existiert, antwortet `/setup` mit 404.

> Damit nicht irgendwer zwischen Deploy und Setup die Instanz übernimmt, verlangt das Setup den Code aus `data/setup-token.txt`. Den kann nur lesen, wer FTP-Zugang hat.

**Passwort vergessen?** `config/config.php` per FTP löschen und `/setup` erneut durchlaufen. Die bestehende Datenbank bleibt dabei erhalten, nur das Passwort wird neu gesetzt.

### Mit SSH bzw. lokal

```bash
php bin/setup.php
```

Das Skript fragt dieselben Angaben ab wie das Web-Setup, einen Setup-Code brauchst du hier nicht.

### Fallback: Document Root lässt sich nicht auf `public/` setzen

Lade das ganze Projekt ins Web-Root. Die `.htaccess` im Projektverzeichnis leitet dann jede Anfrage intern nach `public/` um. Zusätzlich sperren `src/`, `config/`, `data/`, `templates/`, `migrations/` und `bin/` jeweils per eigener `.htaccess` (`Require all denied`) jeden direkten Zugriff.

Wichtig: In die oberste `.htaccess` gehört **kein** `Require all denied`. Apache wendet `.htaccess`-Dateien übergeordneter Verzeichnisse auch auf `public/` an, das würde die App komplett sperren.

## Konfiguration

`config/config.php` gibt ein Array zurück. Fehlende Schlüssel fallen auf die Defaults aus `src/Config.php` zurück, neue Optionen brauchen nach einem Update also keine Anpassung. Alle Optionen mit Erklärung stehen in `config/config.example.php`:

| Schlüssel | Bedeutung |
|---|---|
| `env` | `prod` (Standard) oder `dev`. `dev` zeigt Fehlerdetails und erlaubt HTTP. |
| `base_url` | Öffentliche Adresse ohne `/` am Ende |
| `contact_email` | Kontakt im User-Agent für Overpass, Photon und Nominatim |
| `timezone` | Standard-Zeitzone für die Anzeige |
| `osm.overpass_url` | Overpass-Endpunkt für Orte in der Nähe |
| `osm.overpass_fallback_urls` | Liste weiterer Overpass-Instanzen, die bei Fehlern der Reihe nach versucht werden (`[]` = keine) |
| `osm.search_provider` | `photon` (Standard) oder `nominatim` für die Namenssuche |
| `osm.photon_url`, `osm.nominatim_url` | Endpunkte der Namenssuche |
| `osm.tile_url` | Kachel-Server für die Karte |
| `osm.cache_ttl` | Lebensdauer des Antwort-Caches in Sekunden (Standard: 1 Tag) |
| `osm.timeout` | HTTP-Timeout in Sekunden |

Alles, was du im Alltag änderst (z. B. den Suchradius), liegt unter **Einstellungen** in der App, nicht in der Config.

## Einchecken und OpenStreetMap

1. Die Startseite fragt den Standort per Browser-Geolocation ab. Das geht nur über HTTPS und nur mit deiner Erlaubnis.
2. Der Server fragt bei **Overpass** alle benannten Objekte im Umkreis ab (Standard: 150 m, einstellbar unter *Einstellungen*). Er filtert sie nach Kategorie und sortiert sie nach Entfernung. Berücksichtigt werden `amenity`, `shop`, `tourism`, `leisure`, `historic`, `craft`, `office`, `healthcare`, `sport`, Bahnhöfe, Flughäfen, Gipfel und einige mehr. Offensichtlicher Kleinkram wie Bänke, Mülleimer oder Briefkästen fliegt raus. Die Liste steht in `src/Service/Osm/Categories.php`.
3. Ist das GPS ungenauer als der Radius, wird bis zur Messgenauigkeit gesucht, maximal 1000 m. **Umkreis erweitern** verdoppelt den Radius.
4. Fallback ist die **Namenssuche** über Photon (oder Nominatim), mit deinem Standort als Gewichtung.
5. Mit **Eigener Ort** checkst du mit freiem Namen an deinem aktuellen Standort ein.
6. Hast du an einem OSM-Ort schon eingecheckt, zeigt die Liste „zuletzt am …“ und die Anzahl der Besuche. Dafür werden `osm_type` und `osm_id` gespeichert.

Gespeichert werden bei OSM-Orten die Koordinaten des Orts, bei eigenen Orten dein Standort. Die Zeit steht in UTC in der Datenbank, die Zeitzone deines Geräts wird mitgespeichert.

**Wenn die Umkreissuche nicht geht:** In `/admin` auf **OSM-Verbindung testen** klicken. Du siehst dann für jeden Endpunkt „OK“ oder den Fehlergrund, z. B. `HTTP 429` (Rate-Limit), `HTTP 504` (überlastet) oder einen Verbindungsfehler (Hoster blockt ausgehende Verbindungen). Details landen außerdem in `data/logs/error.log`.

**Fair Use:** Alle Anfragen laufen über den Server, nie direkt aus dem Browser. Sie tragen einen User-Agent mit Kontakt-Mail (`CheckinLog/<version> (+<base_url>; <contact_email>)`) und landen mit TTL in der Tabelle `osm_cache`. Für Overpass teilen sich Anfragen in einem Raster von etwa 11 m einen Cache-Eintrag. Nominatim wird prozessübergreifend auf höchstens 1 Anfrage pro Sekunde gedrosselt (Lock-Datei `data/nominatim.lock`). Die Endpunkte lassen sich in der Config austauschen, z. B. gegen eine eigene Overpass-Instanz.

## Deployment und Updates

Die Datei `.github/workflows/ci.yml` macht zwei Dinge:

- Bei jedem Push und PR laufen Lint, PHPUnit und PHPStan mit PHP 8.5.
- Bei einem Push auf `main` lädt die Action nach grünen Tests per **FTPS** (Port 21) in das FTP-Startverzeichnis hoch.

Dafür müssen diese Repository-Secrets gesetzt sein: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`.

Hochgeladen werden nur Dateien aus dem Repo, Tests und Dev-Konfiguration bleiben draußen. `config/config.php` und alles in `data/` sind gitignored. Die Action löscht nur Dateien, die sie selbst hochgeladen hat, deshalb bleiben Datenbank, Config und Backups auf dem Server unangetastet. Ihren Sync-Stand merkt sich die Action in `.ftp-deploy-sync-state.json` auf dem Server.

**Ablauf eines Updates:**

1. PR mergen. Die Action testet und deployt automatisch.
2. App öffnen. Gibt es ausstehende Migrationen, leitet dich jede Seite nach `/admin` weiter.
3. Auf **Jetzt migrieren** klicken. Vorher wird automatisch ein Backup erstellt.

## Migrationen

Migrationen liegen in `migrations/` und heißen `NNN_beschreibung.php` (fortlaufend, nur Kleinbuchstaben, Ziffern und `_`). Jede Datei gibt eine Funktion zurück:

```php
<?php

declare(strict_types=1);

// What this migration does and why.
return static function (PDO $pdo): void {
    $pdo->exec('ALTER TABLE checkins ADD COLUMN rating INTEGER NULL');
};
```

- Angewendete Migrationen landen in der Tabelle `schema_migrations`.
- Jede Migration läuft in einer eigenen Transaktion. Schlägt eine fehl, wird sie zurückgerollt und der Lauf bricht ab, die nachfolgenden bleiben ausstehend.
- Vor jedem Lauf auf eine bestehende Datenbank legt der Migrator automatisch ein Backup in `data/backups/` an.
- Ausführen geht im Admin-Bereich (`/admin`) oder per CLI mit `php bin/migrate.php`. Den Stand zeigt `php bin/migrate.php --status`.
- Bereits ausgerollte Migrationen nie ändern, sondern immer eine neue anlegen.

## Backup

- Automatische Backups entstehen vor jeder Migration, manuelle über **Backup erstellen** in `/admin`.
- Die Dateien heißen `data/backups/app-YYYYMMDD-HHMMSS-<anlass>.sqlite`, die letzten 20 bleiben erhalten.
- Herunterladen per FTP. Jede Backup-Datei ist eine vollständige SQLite-Datenbank (erzeugt mit `VACUUM INTO`).
- Wiederherstellen: Backup per FTP als `data/app.sqlite` hochladen und die bestehende Datei überschreiben.

## Lokale Entwicklung

```bash
composer install
```

```bash
APP_ENV=dev php -S 127.0.0.1:8080 -t public public/index.php
```

Dann `http://127.0.0.1:8080/setup` aufrufen und danach in `config/config.php` `'env' => 'dev'` setzen, sonst leitet die App auf HTTPS um. Die Geolocation erlauben Browser auf `127.0.0.1` auch ohne HTTPS.

```bash
vendor/bin/phpunit
```

```bash
vendor/bin/phpstan analyse
```

## Ein neues Feature hinzufügen

Am Beispiel „Orte als Favorit markieren“:

1. **Migration** `migrations/00X_favorites.php` anlegen (Tabelle oder Spalte).
2. **Repository** `src/Repository/FavoriteRepository.php`: Hier steht das SQL, und zwar nur hier. Immer Prepared Statements verwenden.
3. Falls nötig, eine **Getter-Methode** in `src/App.php` ergänzen, z. B. `favorites(): FavoriteRepository`.
4. **Controller** `src/Controller/FavoriteController.php` erbt von `Controller`. Er liest den Request, ruft Repositories oder Services auf und gibt eine `Response` zurück.
5. **Routen** in `src/routes.php` eintragen, z. B. `$r->post('/favorite/{id:\d+}', [FavoriteController::class, 'toggle']);`
6. **Template** in `templates/favorites/…` anlegen und jede Ausgabe über `e()` schicken. Formulare brauchen `<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">`.
7. **Tests** in `tests/` schreiben und den Eintrag in `CHANGELOG.md` unter „Unreleased“ ergänzen.

Bestehender Code muss dafür nicht angefasst werden, abgesehen von `routes.php` und ggf. `App.php`. Standardmäßig liegt jede Route hinter dem Login und jeder POST braucht ein gültiges CSRF-Token. Optionen pro Route (`public`, `api`, `ignore_migrations`) sind in `src/routes.php` dokumentiert.

## Sicherheit

- Alle Datenbankzugriffe laufen über Prepared Statements, und zwar nur in Repositories.
- In Templates wird jede Ausgabe mit `e()` escaped.
- Session-Cookies sind `Secure`, `HttpOnly` und `SameSite=Lax`, Sessions liegen in `data/sessions`. Nach dem Login wird die Session-ID neu erzeugt.
- Jedes Formular ist per CSRF-Token geschützt.
- Nach 5 Fehlversuchen pro IP bzw. 25 insgesamt ist der Login 15 Minuten gesperrt.
- HTTPS ist Pflicht (Redirect in `.htaccess` und in der App), dazu kommen HSTS, eine strikte Content Security Policy und `X-Frame-Options: DENY`.
- In Produktion gibt es keine Fehlerdetails. Fehler landen in `data/logs/error.log`.
- `config/` und `data/` sind von außen nicht erreichbar, auch im Fallback-Modus.
