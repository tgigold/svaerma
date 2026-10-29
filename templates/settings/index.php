<?php
/** @var int $radius */
/** @var int $minRadius */
/** @var int $maxRadius */
/** @var array{enabled: bool, limit: string, delay: int, notes: bool, manual: bool} $public */
/** @var string|null $geoapifyHint */
/** @var string $overpassExtra */
/** @var list<string> $overpassConfigured */
/** @var string $version */
/** @var list<string> $applied */
/** @var list<string> $pending */
/** @var list<array{name: string, size: int}> $backups */
/** @var int $dbSize */
/** @var string $phpVersion */
/** @var string $sqliteVersion */
/** @var string $csrf */
$title = 'Einstellungen';
$kb = static fn (int $bytes): string => number_format($bytes / 1024, 0, ',', '.') . ' KB';
?>
<h1 class="h3 mb-3">Einstellungen</h1>

<?php if ($pending !== []): ?>
    <div class="alert alert-warning">
        <h2 class="h6"><i class="bi bi-exclamation-triangle"></i> Ausstehende Migrationen</h2>
        <p class="mb-2">Nach einem Update muss die Datenbank aktualisiert werden. Vorher wird automatisch ein Backup erstellt.</p>
        <ul class="small"><?php foreach ($pending as $m): ?><li><code><?= e($m) ?></code></li><?php endforeach; ?></ul>
        <form method="post" action="/admin/migrate">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button class="btn btn-warning" type="submit"><i class="bi bi-database-up"></i> Jetzt migrieren</button>
        </form>
    </div>
<?php endif; ?>

<form method="post" action="/settings" class="mb-4">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 card-title">Einchecken</h2>
            <label class="form-label" for="nearby_radius">Suchradius für Orte in der Nähe</label>
            <div class="input-group mb-1 input-narrow">
                <input class="form-control" type="number" id="nearby_radius" name="nearby_radius" value="<?= e($radius) ?>"
                       min="<?= e($minRadius) ?>" max="<?= e($maxRadius) ?>" step="10" required>
                <span class="input-group-text">m</span>
            </div>
            <div class="form-text">Standard: 150 m. Bei ungenauem GPS wird bis zur Messgenauigkeit gesucht.</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 card-title">Öffentlich</h2>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="public_enabled" name="public_enabled" value="1"<?= $public['enabled'] ? ' checked' : '' ?>>
                <label class="form-check-label" for="public_enabled">Verlauf, Karte und Detailseiten ohne Login zeigen</label>
            </div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label" for="public_limit">Anzahl öffentlicher Check-ins</label>
                    <input class="form-control" type="number" min="1" id="public_limit" name="public_limit" value="<?= e($public['limit']) ?>" placeholder="alle">
                    <div class="form-text">Die neuesten N, gilt für Verlauf und Karte. Leer = alle.</div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="public_delay_hours">Verzögerung</label>
                    <div class="input-group input-narrow">
                        <input class="form-control" type="number" min="0" max="8760" id="public_delay_hours" name="public_delay_hours" value="<?= e($public['delay']) ?>">
                        <span class="input-group-text">Std.</span>
                    </div>
                    <div class="form-text">Check-ins erscheinen erst nach dieser Zeit öffentlich.</div>
                </div>
            </div>
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="public_notes" name="public_notes" value="1"<?= $public['notes'] ? ' checked' : '' ?>>
                <label class="form-check-label" for="public_notes">Notizen öffentlich zeigen</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="public_manual" name="public_manual" value="1"<?= $public['manual'] ? ' checked' : '' ?>>
                <label class="form-check-label" for="public_manual">Eigene Orte (ohne OSM-Bezug) öffentlich zeigen</label>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 card-title">Ortsdienste</h2>
            <label class="form-label" for="overpass_extra_urls">Weitere Overpass-Endpunkte</label>
            <textarea class="form-control font-monospace small mb-1" id="overpass_extra_urls" name="overpass_extra_urls" rows="2"
                      spellcheck="false" placeholder="https://example.org/api/interpreter"><?= e($overpassExtra) ?></textarea>
            <div class="form-text mb-3">
                Einer pro Zeile, werden nach diesen versucht:
                <?php foreach ($overpassConfigured as $i => $url): ?><?= $i > 0 ? ', ' : '' ?><code><?= e(preg_replace('#^https://#', '', $url)) ?></code><?php endforeach; ?>.
                Danach folgen Geoapify und Photon, deren Ergebnisse zusammengeführt werden.
            </div>
            <label class="form-label" for="geoapify_key">Geoapify-API-Key (optional)</label>
            <input class="form-control mb-1" id="geoapify_key" name="geoapify_key" autocomplete="off" spellcheck="false"
                   placeholder="<?= e($geoapifyHint !== null ? 'gesetzt (' . $geoapifyHint . ') – leer lassen = unverändert' : 'nicht gesetzt') ?>">
            <div class="form-text mb-2">
                Fallback für die Umkreissuche, wenn Overpass nicht antwortet. Kostenloser Key mit 3.000 Anfragen/Tag unter
                <a href="https://myprojects.geoapify.com/" rel="noopener" target="_blank">myprojects.geoapify.com</a>.
            </div>
            <?php if ($geoapifyHint !== null): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="geoapify_remove" name="geoapify_remove" value="1">
                    <label class="form-check-label" for="geoapify_remove">Key entfernen</label>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <button class="btn btn-primary" type="submit">Speichern</button>
</form>

<h2 class="h5 mb-3" id="system">System</h2>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 card-title">Status</h3>
                <dl class="row small mb-0">
                    <dt class="col-6">Version</dt><dd class="col-6"><?= e(App\App::NAME) ?> <?= e($version) ?></dd>
                    <dt class="col-6">PHP</dt><dd class="col-6"><?= e($phpVersion) ?></dd>
                    <dt class="col-6">SQLite</dt><dd class="col-6"><?= e($sqliteVersion) ?></dd>
                    <dt class="col-6">Datenbank</dt><dd class="col-6"><?= e($kb($dbSize)) ?></dd>
                    <dt class="col-6">Migrationen</dt><dd class="col-6"><?= e(count($applied)) ?> angewendet</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 card-title">Backups</h3>
                <p class="small text-body-secondary">Liegen in <code>data/backups/</code>, die letzten 20 bleiben erhalten. Herunterladen per FTP.</p>
                <form method="post" action="/admin/backup" class="mb-3">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-archive"></i> Backup erstellen</button>
                </form>
                <?php if ($backups === []): ?>
                    <p class="small mb-0">Noch keine Backups.</p>
                <?php else: ?>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach (array_slice($backups, 0, 5) as $b): ?>
                            <li><code><?= e($b['name']) ?></code> · <?= e($kb($b['size'])) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h3 class="h6 card-title">OpenStreetMap-Dienste</h3>
                <p class="small text-body-secondary">Prüft, ob der Server Overpass, Geoapify und die Ortssuche erreicht.</p>
                <form method="post" action="/admin/osm-test">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-broadcast"></i> Verbindung testen</button>
                </form>
            </div>
        </div>
    </div>
</div>
