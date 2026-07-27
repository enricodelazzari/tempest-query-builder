<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\DefaultSort;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidSortQuery;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Direction;

final class SortsApplier extends Applier
{
    public function apply(SelectQueryBuilder $query): void
    {
        $requested = $this->split(
            $this->parameter($this->config->sortParameter) ?? [],
        );

        if ($requested === []) {
            $this->applyDefaults($query);

            return;
        }

        /** @var AllowedSort[] $allowed */
        $allowed = $this->reflector->getAttributes(AllowedSort::class);

        $this->guard($requested, $allowed);

        $applied = 0;

        foreach ($requested as $item) {
            $descending = str_starts_with($item, '-');
            $name = $descending ? substr($item, 1) : $item;

            $sort = array_find(
                $allowed,
                static fn (AllowedSort $sort): bool => $sort->name === $name,
            );

            if ($sort === null) {
                continue;
            }

            $sort->sort->apply(
                $query,
                $sort->column(),
                $descending ? Direction::DESC : Direction::ASC,
            );

            $applied++;
        }

        if ($applied === 0) {
            $this->applyDefaults($query);
        }
    }

    private function applyDefaults(SelectQueryBuilder $query): void
    {
        /** @var DefaultSort[] $defaults */
        $defaults = $this->reflector->getAttributes(DefaultSort::class);

        foreach ($defaults as $default) {
            $default->sort->apply($query, $default->name, $default->direction);
        }
    }

    /**
     * @param  string[]  $requested
     * @param  AllowedSort[]  $allowed
     */
    private function guard(array $requested, array $allowed): void
    {
        if (! $this->config->strict) {
            return;
        }

        $names = array_map(static fn (AllowedSort $sort): string => $sort->name, $allowed);

        $unknown = array_diff(
            array_map(static fn (string $item): string => ltrim($item, '-'), $requested),
            $names,
        );

        if ($unknown !== []) {
            throw InvalidSortQuery::forNames($unknown, $names);
        }
    }
}
