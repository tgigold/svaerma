<?php

declare(strict_types=1);

// CLI setup: php bin/setup.php – same steps as the web setup at /setup.

require __DIR__ . '/../src/bootstrap.php';

use App\Config;
use App\Service\Setup;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

function ask(string $question, string $default = '', bool $hidden = false): string
{
    $suffix = $default !== '' ? " [$default]" : '';
    echo $question . $suffix . ': ';
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
    }
    $answer = trim((string) fgets(STDIN));
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty echo');
        echo "\n";
    }
    return $answer === '' ? $default : $answer;
}

if (Config::exists()) {
    fwrite(STDERR, "config/config.php existiert bereits. Zum Neu-Einrichten erst löschen.\n");
    exit(1);
}

$setup = new Setup(Config::path(), APP_ROOT . '/data', APP_ROOT . '/migrations');
if ($problems = $setup->checkRequirements()) {
    fwrite(STDERR, implode("\n", $problems) . "\n");
    exit(1);
}

$input = [
    'contact_email' => ask('Kontakt-Mail'),
    'base_url' => rtrim(ask('Adresse der App', 'https://checkin.gigold.me'), '/'),
    'timezone' => ask('Zeitzone', 'Europe/Berlin'),
    'password' => ask('Passwort', '', true),
];
if ($input['password'] !== ask('Passwort wiederholen', '', true)) {
    fwrite(STDERR, "Die Passwörter stimmen nicht überein.\n");
    exit(1);
}
if ($errors = $setup->validate($input)) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

$applied = $setup->run($input);
echo "Fertig. Migrationen: " . (implode(', ', $applied) ?: 'keine') . "\n";
