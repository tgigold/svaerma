<?php
/** @var string $version */
/** @var list<string> $applied */
/** @var list<string> $pending */
/** @var list<array{name: string, size: int, time: int}> $backups */
/** @var int $dbSize */
/** @var string $phpVersion */
/** @var string $sqliteVersion */
/** @var string $csrf */
$title = 'Admin';
$kb = static fn (int $bytes): string => number_format($bytes / 1024, 0, ',', '.') . ' KB';
?>
<h1 class="h3 mb-3">Admin</h1>

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

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 card-title">System</h2>
                <dl class="row small mb-0">
                    <dt class="col-6">App-Version</dt><dd class="col-6"><?= e($version) ?></dd>
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
                <h2 class="h6 card-title">Backups</h2>
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
</div>
