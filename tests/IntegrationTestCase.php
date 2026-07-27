<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests;

use Tempest\Discovery\DiscoveryLocation;
use Tempest\Framework\Testing\IntegrationTest;

abstract class IntegrationTestCase extends IntegrationTest
{
    protected string $root = __DIR__.'/../';

    /**
     * Tempest assumes the `Tests` namespace for a `tests` directory, so the
     * package's own namespace has to be registered explicitly. Only the support
     * classes are discovered; the test files themselves are Pest's business.
     *
     * @return DiscoveryLocation[]
     */
    protected function discoverTestLocations(): array
    {
        return [
            new DiscoveryLocation(__NAMESPACE__.'\\Support\\', __DIR__.'/Support'),
        ];
    }
}
