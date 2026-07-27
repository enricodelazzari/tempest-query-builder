<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

final class IncludesApplier extends Applier
{
    public function apply(SelectQueryBuilder $query): void
    {
        $allowed = $this->allowed(AllowedInclude::class);
        $requested = $this->split($this->parameter($this->config->includeParameter) ?? '');

        $this->guard($requested, $allowed, InvalidIncludeQuery::class);

        foreach ($requested as $name) {
            // Only null when strict mode is off, since `guard` threw otherwise.
            $include = $this->find($allowed, $name);

            if ($include === null) {
                continue;
            }

            $include->include->apply($query, $include->relation());
        }
    }
}
