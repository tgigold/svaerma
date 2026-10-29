# svaerma

Ein persönliches Check-in-Log als Swarm-Ersatz: Du checkst bewusst an Orten ein und baust dir so ein Archiv der Orte auf, an denen du warst. Es gibt kein Social, kein Hintergrund-Tracking und nur einen Nutzer. Verlauf und Karte lassen sich optional öffentlich zeigen. Die Ortsdaten kommen ausschließlich aus OpenStreetMap.

Live unter `https://checkin.gigold.me`.

## Inhalt

- [Anforderungen](#anforderungen)
- [Verzeichnisstruktur](#verzeichnisstruktur)
- [Installation](#installation)
- [Konfiguration](#konfiguration)
- [Einchecken und OpenStreetMap](#einchecken-und-openstreetmap)
- [Ansichten und URLs](#ansichten-und-urls)
- [API](#api)
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
| `osm.overpass_timeout` | Timeout pro Overpass-Endpunkt in Sekunden (Standard: 12), danach greift der Fallback |
| `osm.photon_reverse_url` | Photon-Reverse-Endpunkt: Umkreissuche, wenn Overpass ausfällt |
| `osm.overpass_fallback_urls` | Liste weiterer Overpass-Instanzen, die bei Fehlern der Reihe nach versucht werden (Standard: keine, weitere auch in den Einstellungen) |
| `osm.geoapify_url` | Geoapify-Places-Endpunkt (aktiv nur mit API-Key aus den Einstellungen) |
| `osm.search_provider` | `photon` (Standard) oder `nominatim` für die Namenssuche |
| `osm.photon_url`, `osm.nominatim_url` | Endpunkte der Namenssuche |
| `osm.tile_url` | Kachel-Server für die Karte |
| `osm.cache_ttl` | Lebensdauer des Antwort-Caches in Sekunden (Standard: 1 Tag) |
| `osm.timeout` | HTTP-Timeout in Sekunden |

Alles, was du im Alltag änderst (Suchradius, öffentlicher Modus, Geoapify-Key), liegt unter **Einstellungen** (`/settings`) in der App, nicht in der Config. Dort findest du auch Migrationen, Backups und die OSM-Diagnose.

## Einchecken und OpenStreetMap

1. Die Startseite fragt den Standort per Browser-Geolocation ab. Das geht nur über HTTPS und nur mit deiner Erlaubnis.
2. Der Server fragt bei **Overpass** alle benannten Objekte im Umkreis ab (Standard: 150 m, einstellbar unter *Einstellungen*). Er filtert sie nach Kategorie und sortiert sie nach Entfernung. Berücksichtigt werden `amenity`, `shop`, `tourism`, `leisure`, `historic`, `craft`, `office`, `healthcare`, `sport`, Bahnhöfe, Flughäfen, Gipfel und einige mehr. Offensichtlicher Kleinkram wie Bänke, Mülleimer oder Briefkästen fliegt raus. Die Liste steht in `src/Service/Osm/Categories.php`.
   Ist Overpass nicht erreichbar (auch keiner der weiteren Endpunkte aus Config und Einstellungen), fragt die App **Geoapify** (nur mit API-Key, Places API) und die **Reverse-Suche von Photon** ab und führt beide Listen zusammen. Duplikate werden entfernt: gleiches OSM-Objekt, oder gleicher Name innerhalb von 40 m. Ein Hinweis zeigt an, woher die Liste stammt. Nach einem kompletten Overpass-Ausfall wird Overpass 5 Minuten lang übersprungen.
3. Ist das GPS ungenauer als der Radius, wird bis zur Messgenauigkeit gesucht, maximal 1000 m. **Umkreis erweitern** verdoppelt den Radius.
4. Fallback ist die **Namenssuche** über Photon (oder Nominatim), mit deinem Standort als Gewichtung.
5. Mit **Eigener Ort** checkst du mit freiem Namen an deinem aktuellen Standort ein.
6. Hast du an einem OSM-Ort schon eingecheckt, zeigt die Liste „zuletzt am …“ und die Anzahl der Besuche. Dafür werden `osm_type` und `osm_id` gespeichert.

Gespeichert werden bei OSM-Orten die Koordinaten des Orts, bei eigenen Orten dein Standort. Die Zeit steht in UTC in der Datenbank, die Zeitzone deines Geräts wird mitgespeichert.

**Wenn die Umkreissuche nicht geht:** In den Einstellungen (`/settings`) unter *OpenStreetMap-Dienste* auf **Verbindung testen** klicken. Du siehst dann für jeden Endpunkt „OK“ oder den Fehlergrund, z. B. `HTTP 429` (Rate-Limit), `HTTP 504` (überlastet) oder einen Verbindungsfehler (Hoster blockt ausgehende Verbindungen). Details landen außerdem in `data/logs/error.log`.

**Fair Use:** Alle Anfragen laufen über den Server, nie direkt aus dem Browser. Sie tragen einen User-Agent mit Kontakt-Mail (`svaerma/<version> (+<base_url>; <contact_email>)`) und landen mit TTL in der Tabelle `osm_cache`. Für Overpass teilen sich Anfragen in einem Raster von etwa 11 m einen Cache-Eintrag. Nominatim wird prozessübergreifend auf höchstens 1 Anfrage pro Sekunde gedrosselt (Lock-Datei `data/nominatim.lock`). Die Endpunkte lassen sich in der Config austauschen, z. B. gegen eine eigene Overpass-Instanz.

## Ansichten und URLs

| URL | Inhalt |
|---|---|
| `/` | Einchecken |
| `/checkins` | Verlauf, nach Tagen gruppiert, 50 pro Seite (`?page=2`) |
| `/checkin/123-cafe-xyz` | Detailseite mit Karte und allen Besuchen am selben Ort |
| `/checkin/123/edit` | Name, Notiz, Zeitpunkt und Zeitzone bearbeiten |
| `/map` | Karte mit allen Check-ins |
| `/settings` | Einstellungen, Migrationen, Backups, OSM-Diagnose (`/admin` leitet hierhin um) |

Maßgeblich ist immer die **ID**, der Slug dahinter ist nur Kosmetik. Fehlt er, ist er veraltet (z. B. nach einer Umbenennung) oder falsch, leitet die App per **301** auf die korrekte URL um. Links bleiben damit dauerhaft gültig.

### Öffentlicher Modus

Unter *Einstellungen → Öffentlich* lassen sich **Verlauf, Karte und Detailseiten** ohne Login freigeben. Standardmäßig ist das aus. Einstellbar sind:

- **Anzahl**: nur die neuesten N Check-ins (leer = alle). Die Grenze gilt für Liste und Karte gemeinsam. Ältere Detailseiten liefern für Gäste 404.
- **Verzögerung**: Check-ins werden erst nach X Stunden öffentlich (Standard: 2), damit niemand live sieht, wo du gerade bist.
- **Notizen** und **eigene Orte** (ohne OSM-Bezug, z. B. „Bei Oma“) lassen sich einzeln ausblenden.

Gäste sehen keine Bearbeiten-, Lösch- oder Einchecken-Funktionen. Die Startseite `/` leitet sie in den Verlauf, zum Login geht es oben rechts. Technisch sind die betroffenen Routen in `src/routes.php` mit `'guest' => true` markiert. Was Gäste sehen, regelt `App\Model\Visibility` im `CheckinRepository`.

Uhrzeiten werden in der **Ortszeit des Check-ins** angezeigt. Ein Check-in in Tokio um 12:00 bleibt also 12:00, auch wenn du die Liste in Berlin ansiehst.

## API

Die REST-API unter `/api/v1` ist vor allem für den iOS-Kurzbefehl gedacht.

**Token:** Unter *Einstellungen → API* einen Token erzeugen (z. B. „iPhone“). Er wird **genau einmal** angezeigt, gespeichert wird nur sein SHA-256-Hash. Dort lässt er sich auch jederzeit widerrufen. Jede Anfrage braucht den Header:

```
Authorization: Bearer svm_…
```

Fehlversuche zählen zum selben Rate-Limit wie der Login (5 pro IP und 15 Minuten). Danach antwortet die API mit `429`.

### Endpunkte

| Methode | Pfad | Beschreibung |
|---|---|---|
| `GET` | `/api/v1/places/nearby?lat=…&lon=…[&radius=…]` | Orte in der Nähe (gleiche Fallback-Kette wie im Web) |
| `POST` | `/api/v1/checkins` | Check-in anlegen |
| `GET` | `/api/v1/checkins[?limit=50&offset=0]` | Check-ins, neueste zuerst (max. 200 pro Seite, `next` verweist auf die nächste Seite) |
| `GET` | `/api/v1/checkins/{id}` | Ein Check-in |

**`places/nearby`** liefert `places` (Liste) und zusätzlich `choices`: ein Objekt aus lesbarem Label (z. B. `"Café Müller · Café · 13 m"`) und Ort. Genau das braucht „Aus Liste auswählen“ im Kurzbefehl.

```json
{
  "source": "overpass",
  "notice": null,
  "radius": 150,
  "places": [{ "name": "Café Müller", "lat": 52.5201, "lon": 13.4051, "osm_type": "node", "osm_id": 1001,
               "category": "Café", "address": "Rosenthaler Straße 12", "distance": 13,
               "visits": 2, "last_visit": "28.10.2026", "label": "Café Müller · Café · 13 m" }],
  "choices": { "Café Müller · Café · 13 m": { "name": "Café Müller", "…": "…" } }
}
```

**`POST /api/v1/checkins`** nimmt JSON oder Formulardaten an, entweder als Einzelfelder oder als ganzes Ort-Objekt aus `places/nearby`:

```json
{ "place": { "name": "Café Müller", "lat": 52.5201, "lon": 13.4051, "osm_type": "node", "osm_id": 1001 }, "note": "Flat White" }
```

```json
{ "lat": 52.52, "lon": 13.405, "place_name": "Bei Oma", "note": "…", "timezone": "Europe/Berlin", "checked_in_at": "2026-10-29T10:00:00Z" }
```

Ohne Angaben sind `checked_in_at` jetzt und `timezone` die Standard-Zeitzone aus der Config. Die Antwort ist `201` mit dem Check-in (inkl. `url` zur Detailseite), bei ungültigen Daten `422` mit `errors`.

```bash
curl -H "Authorization: Bearer $TOKEN" "https://checkin.gigold.me/api/v1/places/nearby?lat=51.4&lon=12.1"
```

```bash
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d '{"lat":51.4,"lon":12.1,"place_name":"Test"}' https://checkin.gigold.me/api/v1/checkins
```

### Beispiel: iOS-Kurzbefehl „Einchecken“

In der Kurzbefehle-App einen neuen Kurzbefehl anlegen und diese Aktionen in dieser Reihenfolge hinzufügen:

1. **Text**: deinen Token einfügen (`svm_…`). Die Variable z. B. in *Token* umbenennen.
2. **Aktuellen Standort abrufen**
3. **Inhalte von URL abrufen**
   - URL: `https://checkin.gigold.me/api/v1/places/nearby?lat=` *Breitengrad* `&lon=` *Längengrad*. Die Variablen über *Aktueller Standort → Breitengrad/Längengrad* einfügen.
   - Methode: `GET`. Header: `Authorization` = `Bearer ` *Token*
4. **Wörterbuchwert abrufen**: *Wert* für Schlüssel `choices` in *Inhalte von URL*
5. **Wörterbuchwert abrufen**: *Alle Schlüssel* in *Wörterbuchwert* (aus Schritt 4)
6. **Aus Liste auswählen**: *Schlüssel*, Eingabeaufforderung „Wo bist du?“
7. **Wörterbuchwert abrufen**: *Wert* für Schlüssel *Ausgewähltes Objekt* in *Wörterbuchwert* (aus Schritt 4). Das ist der gewählte Ort.
8. **Nach Eingabe fragen**: „Notiz?“ (Text, optional leer lassen)
9. **Inhalte von URL abrufen**
   - URL: `https://checkin.gigold.me/api/v1/checkins`, Methode: `POST`
   - Header: `Authorization` = `Bearer ` *Token*
   - Anfragetext: **JSON** mit zwei Feldern: `place` (Typ *Wörterbuch*) = *Wörterbuchwert* aus Schritt 7, `note` (Typ *Text*) = *Eingabe*
10. **Wörterbuchwert abrufen**: *Wert* für `place_name` in *Inhalte von URL*, danach **Mitteilung anzeigen**: „Eingecheckt: …“

Tipp: Über *Zum Home-Bildschirm* oder die Aktionstaste bist du mit einem Tipp dabei. Liefert Schritt 3 einen Fehler, steht der Grund im Feld `error` (z. B. `401` = Token falsch oder widerrufen).

## Deployment und Updates

Die Datei `.github/workflows/ci.yml` macht zwei Dinge:

- Bei jedem Push und PR laufen Lint, PHPUnit und PHPStan mit PHP 8.5.
- Bei einem Push auf `main` lädt die Action nach grünen Tests per **FTPS** (Port 21) in das FTP-Startverzeichnis hoch.

Dafür müssen diese Repository-Secrets gesetzt sein: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`.

Hochgeladen werden nur Dateien aus dem Repo, Tests und Dev-Konfiguration bleiben draußen. `config/config.php` und alles in `data/` sind gitignored. Die Action löscht nur Dateien, die sie selbst hochgeladen hat, deshalb bleiben Datenbank, Config und Backups auf dem Server unangetastet. Ihren Sync-Stand merkt sich die Action in `.ftp-deploy-sync-state.json` auf dem Server.

**Ablauf eines Updates:**

1. PR mergen. Die Action testet und deployt automatisch.
2. App öffnen. Gibt es ausstehende Migrationen, leitet dich jede Seite zu den Einstellungen weiter.
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
- Ausführen geht in den Einstellungen (`/settings`) oder per CLI mit `php bin/migrate.php`. Den Stand zeigt `php bin/migrate.php --status`.
- Bereits ausgerollte Migrationen nie ändern, sondern immer eine neue anlegen.

## Backup

- Automatische Backups entstehen vor jeder Migration, manuelle über **Backup erstellen** in den Einstellungen.
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

Bestehender Code muss dafür nicht angefasst werden, abgesehen von `routes.php` und ggf. `App.php`. Standardmäßig liegt jede Route hinter dem Login und jeder POST braucht ein gültiges CSRF-Token. Optionen pro Route (`public`, `guest`, `api`, `ignore_migrations`) sind in `src/routes.php` dokumentiert.

## Sicherheit

- Alle Datenbankzugriffe laufen über Prepared Statements, und zwar nur in Repositories.
- In Templates wird jede Ausgabe mit `e()` escaped.
- Session-Cookies sind `Secure`, `HttpOnly` und `SameSite=Lax`, Sessions liegen in `data/sessions`. Nach dem Login wird die Session-ID neu erzeugt.
- Jedes Formular ist per CSRF-Token geschützt.
- Nach 5 Fehlversuchen pro IP bzw. 25 insgesamt ist der Login 15 Minuten gesperrt. Falsche API-Tokens zählen mit.
- API-Tokens werden nur als SHA-256-Hash gespeichert und lassen sich jederzeit widerrufen. API-Anfragen nutzen keine Session und brauchen daher kein CSRF-Token.
- HTTPS ist Pflicht (Redirect in `.htaccess` und in der App), dazu kommen HSTS, eine strikte Content Security Policy und `X-Frame-Options: DENY`.
- `Referrer-Policy: strict-origin-when-cross-origin`: Fremde Server (z. B. OSM-Kacheln, die einen Referer verlangen) sehen nur die Domain, nie den Pfad.
- In Produktion gibt es keine Fehlerdetails. Fehler landen in `data/logs/error.log`.
- `config/` und `data/` sind von außen nicht erreichbar, auch im Fallback-Modus.
