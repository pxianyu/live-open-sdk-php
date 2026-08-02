<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Company\\LiveOpenSdk\\' => dirname(__DIR__) . '/src/',
        'Company\\LiveOpenSdk\\Tests\\' => __DIR__ . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (strpos($class, $prefix) !== 0) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $path = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require $path;
        }
    }
});
