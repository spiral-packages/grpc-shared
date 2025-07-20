<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/generator/src',
        __DIR__ . '/src',
    ]);

    // Register rules for PHP 8.4 migration
    $rectorConfig->sets([
        SetList::PHP_83,
        LevelSetList::UP_TO_PHP_83,
    ]);

    // Skip vendor directories
    $rectorConfig->skip([
        __DIR__ . '/vendor',
    ]);
};
