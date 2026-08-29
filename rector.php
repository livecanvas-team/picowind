<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php73\Rector\String_\SensitiveHereNowDocRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withParallel(timeoutSeconds: 300)
    ->withSkip([
        SensitiveHereNowDocRector::class,
    ])
    ->withPreparedSets(
        naming: true,
        codeQuality: true,
        codingStyle: true,
    )
    ->withPhpSets(php82: true)
    ->withDowngradeSets(php82: true);
