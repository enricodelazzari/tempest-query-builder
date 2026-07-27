<?php

declare(strict_types=1);

/*
 * Note on `composer.json`: `phpstan/phpstan` is held at `~2.1.38` and
 * `composer/pcre` below 3.4 for Rector's sake, not by preference.
 *
 * Rector 2.3.8 — the version Tempest pins — reaches into
 * `PHPStan\Parser\RichParser::$container`, a property that PHPStan dropped in
 * 2.2. Its own constraint (`^2.1.38`) still allows 2.2, so Composer happily
 * resolves a pair that fatals on startup. Holding PHPStan at 2.1 fixes it, but
 * `composer/pcre` 3.4 declares `conflict: phpstan/phpstan <2.2.2`, so that has
 * to give way too.
 *
 * Both pins can go once Rector supports PHPStan 2.2.
 */

use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withSkip([
        // Rewrites a plain `if ($sort === null)` into an `instanceof` check
        // against a fully qualified class name, which reads worse for no gain.
        FlipTypeControlToUseExclusiveTypeRector::class,

        // The package's private helpers are static because they are pure, not
        // because of where they happen to be called from. One of them is called
        // from a static closure, where `$this` does not exist.
        LocallyCalledStaticMethodToNonStaticRector::class,
    ]);
