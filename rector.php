<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
    )
    ->withSkip([
        // The application does not declare strict types; opting in is a
        // deliberate, runtime-affecting change rather than a cleanup.
        SafeDeclareStrictTypesRector::class,

        // Rewrites readable `=== null` guards into `instanceof` checks with
        // inline fully-qualified class names.
        FlipTypeControlToUseExclusiveTypeRector::class,
    ]);
