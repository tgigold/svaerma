<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\Tests\\')) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 10)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
