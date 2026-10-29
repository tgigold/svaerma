<?php

declare(strict_types=1);

// CLI: php bin/import-swarm.php [--dry-run] checkins1.json [checkins2.json …]

require __DIR__ . '/../src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$args = array_slice($_SERVER['argv'] ?? [], 1);
$dryRun = in_array('--dry-run', $args, true);
$paths = array_values(array_filter($args, static fn (string $a): bool => $a !== '--dry-run'));
if ($paths === []) {
    fwrite(STDERR, "Aufruf: php bin/import-swarm.php [--dry-run] checkins1.json [checkins2.json …]\n");
    exit(1);
}

$files = [];
foreach ($paths as $path) {
    if (!is_readable($path)) {
        fwrite(STDERR, "Datei nicht lesbar: $path\n");
        exit(1);
    }
    $files[basename($path)] = (string) file_get_contents($path);
}

$app = new App\App(App\Config::load());
if ($app->migrator()->pending() !== []) {
    fwrite(STDERR, "Ausstehende Migrationen – erst php bin/migrate.php ausführen.\n");
    exit(1);
}

$report = $app->swarmImporter()->import($files, $dryRun);
printf(
    "%s: %d Einträge, %d %s, %d schon vorhanden, %d übersprungen\n",
    $dryRun ? 'Probelauf' : 'Import',
    $report['total'],
    $report['imported'],
    $dryRun ? 'würden importiert' : 'importiert',
    $report['duplicates'],
    count($report['skipped']),
);
foreach ($report['skipped'] as $s) {
    printf("  übersprungen  %s  %s: %s\n", $s['file'], $s['id'], $s['reason']);
}
