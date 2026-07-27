<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests;

use Tempest\Discovery\DiscoveryLocation;
use Tempest\Framework\Testing\IntegrationTest;

use function Tempest\Support\Path\normalize;

abstract class IntegrationTestCase extends IntegrationTest
{
    /**
     * Tempest assumes the `Tests` namespace for a `tests` directory, so the
     * package's own namespace has to be registered explicitly. Only the support
     * classes are discovered; the test files themselves are Pest's business.
     *
     * The path is normalized because discovery matches it against the paths it
     * walks, which use forward slashes even on Windows.
     *
     * @return DiscoveryLocation[]
     */
    protected function discoverTestLocations(): array
    {
        return [
            new DiscoveryLocation(__NAMESPACE__.'\\Support\\', normalize(__DIR__, '/Support')),
        ];
    }
}
