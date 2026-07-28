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
        $allowed = $this->allowed(AllowedSort::class);
        $requested = $this->split($this->parameter($this->config->sortParameter) ?? '');

        $this->guard(
            array_map($this->name(...), $requested),
            $allowed,
            InvalidSortQuery::class,
            $this->config->strictSorts,
            reported: $requested,
        );

        /** @var array<array{AllowedSort, Direction}> $sorts */
        $sorts = [];

        foreach ($requested as $item) {
            // Only null when strict mode is off, since `guard` threw otherwise.
            $sort = $this->find($allowed, $this->name($item));

            if ($sort !== null) {
                $sorts[] = [$sort, $this->direction($item)];
            }
        }

        if ($sorts === []) {
            $this->applyDefaults($query);

            return;
        }

        foreach ($sorts as [$sort, $direction]) {
            $sort->sort->apply($query, $sort->column(), $direction);
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
     * Strips the leading hyphen that marks a descending sort.
     */
    private function name(string $sort): string
    {
        return $this->isDescending($sort) ? substr($sort, 1) : $sort;
    }

    private function direction(string $sort): Direction
    {
        return $this->isDescending($sort) ? Direction::DESC : Direction::ASC;
    }

    private function isDescending(string $sort): bool
    {
        return str_starts_with($sort, '-');
    }
}
