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
        /** @var AllowedInclude[] $allowed */
        $allowed = $this->reflector->getAttributes(AllowedInclude::class);

        $requested = $this->split(
            $this->parameter($this->config->includeParameter) ?? [],
        );

        if ($requested === []) {
            return;
        }

        $this->guard($requested, $allowed);

        foreach ($requested as $name) {
            $include = array_find(
                $allowed,
                static fn (AllowedInclude $include): bool => $include->name === $name,
            );

            if ($include === null) {
                continue;
            }

            $include->include->apply($query, $include->relation());
        }
    }

    /**
     * @param  string[]  $requested
     * @param  AllowedInclude[]  $allowed
     */
    private function guard(array $requested, array $allowed): void
    {
        if (! $this->config->strict) {
            return;
        }

        $names = array_map(static fn (AllowedInclude $include): string => $include->name, $allowed);
        $unknown = array_diff($requested, $names);

        if ($unknown !== []) {
            throw InvalidIncludeQuery::forNames($unknown, $names);
        }
    }
}
