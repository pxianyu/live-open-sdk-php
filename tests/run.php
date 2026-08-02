<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$testFiles = glob(__DIR__ . '/Unit/*Test.php');
sort($testFiles);

$failures = [];
$testsRun = 0;

foreach ($testFiles as $file) {
    require_once $file;
    $className = 'Company\\LiveOpenSdk\\Tests\\Unit\\' . basename($file, '.php');

    if (!class_exists($className)) {
        $failures[] = 'Missing test class for ' . basename($file);
        continue;
    }

    $instance = new $className();
    $methods = array_filter(get_class_methods($instance), static fn (string $method): bool => strpos($method, 'test') === 0);
    sort($methods);

    foreach ($methods as $method) {
        $testsRun++;

        try {
            $instance->{$method}();
            echo "PASS {$className}::{$method}\n";
        } catch (Throwable $throwable) {
            $failures[] = "FAIL {$className}::{$method} - {$throwable->getMessage()}";
        }
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . PHP_EOL);
    }

    fwrite(STDERR, sprintf("%d/%d tests failed.\n", count($failures), $testsRun));
    exit(1);
}

echo sprintf("All %d tests passed.\n", $testsRun);
