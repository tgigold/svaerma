<?php
/** @var array{total: int, imported: int, duplicates: int, skipped: list<array{file: string, id: string, reason: string}>, dry_run: bool} $report */
/** @var list<string> $files */
use App\Format;

$title = 'Swarm-Import';
?>
<nav class="small mb-2"><a href="/settings#import"><i class="bi bi-chevron-left"></i> Einstellungen</a></nav>
<h1 class="h3 mb-3">Swarm-Import<?= $report['dry_run'] ? ' – Probelauf' : '' ?></h1>

<div class="alert <?= $report['dry_run'] ? 'alert-info' : 'alert-success' ?>">
    <?php if ($report['dry_run']): ?>
        <strong><?= e(Format::number($report['imported'])) ?></strong> Check-ins würden importiert. Es wurde noch nichts gespeichert.
    <?php else: ?>
        <strong><?= e(Format::number($report['imported'])) ?></strong> Check-ins importiert.
    <?php endif; ?>
</div>

<dl class="row small">
    <dt class="col-6 col-sm-3">Dateien</dt><dd class="col-6 col-sm-9"><?= e(implode(', ', $files)) ?></dd>
    <dt class="col-6 col-sm-3">Einträge gesamt</dt><dd class="col-6 col-sm-9"><?= e(Format::number($report['total'])) ?></dd>
    <dt class="col-6 col-sm-3"><?= $report['dry_run'] ? 'Würden importiert' : 'Importiert' ?></dt><dd class="col-6 col-sm-9"><?= e(Format::number($report['imported'])) ?></dd>
    <dt class="col-6 col-sm-3">Schon vorhanden</dt><dd class="col-6 col-sm-9"><?= e(Format::number($report['duplicates'])) ?></dd>
    <dt class="col-6 col-sm-3">Übersprungen</dt><dd class="col-6 col-sm-9"><?= e(Format::number(count($report['skipped']))) ?></dd>
</dl>

<?php if ($report['skipped'] !== []): ?>
    <h2 class="h6">Übersprungene Einträge</h2>
    <div class="table-responsive mb-3">
        <table class="table table-sm small">
            <thead><tr><th>Datei</th><th>Swarm-ID</th><th>Grund</th></tr></thead>
            <tbody>
                <?php foreach (array_slice($report['skipped'], 0, 200) as $s): ?>
                    <tr><td><?= e($s['file']) ?></td><td><code><?= e($s['id']) ?></code></td><td><?= e($s['reason']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($report['skipped']) > 200): ?><p class="small text-body-secondary">… und <?= e(count($report['skipped']) - 200) ?> weitere.</p><?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($report['dry_run'] && $report['imported'] > 0): ?>
    <p class="small">Passt alles? Dann die Dateien noch einmal auswählen und den Probelauf abwählen.</p>
<?php endif; ?>
<a class="btn btn-primary" href="/settings#import">Zurück</a>
<?php if (!$report['dry_run'] && $report['imported'] > 0): ?>
    <a class="btn btn-outline-primary" href="/checkins">Zum Verlauf</a>
<?php endif; ?>
