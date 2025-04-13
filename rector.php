<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/database',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/resources/views',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkipPath(__DIR__.'/bootstrap/cache')
    ->withSkipPath(__DIR__.'/app/Providers/AppServiceProvider.php')
    ->withPhpSets(php83: true)
    ->withTypeCoverageLevel(0)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
    )
    ->withImportNames();
