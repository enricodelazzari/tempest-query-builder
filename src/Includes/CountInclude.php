<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Includes;

/**
 * Counts the records of a relation, e.g. `?include=booksCount` selecting how
 * many books each author has.
 */
final readonly class CountInclude extends RelationAggregate
{
    #[\Override]
    protected function expression(string $key, string $table): string
    {
        return sprintf('COUNT(%s)', $key);
    }
}
