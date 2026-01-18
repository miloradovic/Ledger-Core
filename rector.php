<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPhpVersion(\Rector\ValueObject\PhpVersion::PHP_83)
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/app/Http/Middleware/HandleInertiaRequests.php',
        __DIR__ . '/tests/Pest.php',
        __DIR__ . '/tests/TestCase.php',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
    ])
    ->withRules([
    ]);
