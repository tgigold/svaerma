<?php

declare(strict_types=1);

// CLI migrations: php bin/migrate.php [--status]

require __DIR__ . '/../src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$app = new App\App(App\Config::load());
$migrator = $app->migrator();

if (in_array('--status', $_SERVER['argv'] ?? [], true)) {
    echo 'Angewendet: ' . count($migrator->applied()) . "\n";
    foreach ($migrator->pending() as $version) {
        echo "Ausstehend: $version\n";
    }
    exit(0);
}

$result = $migrator->migrate();
if ($result['applied'] === []) {
    echo "Keine ausstehenden Migrationen.\n";
    exit(0);
}
if ($result['backup'] !== null) {
    echo "Backup: {$result['backup']}\n";
}
foreach ($result['applied'] as $version) {
    echo "Migriert: $version\n";
}
