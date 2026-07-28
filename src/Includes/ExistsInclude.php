<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Includes;

/**
 * Reports whether a relation has any record at all, e.g. `?include=booksExists`.
 *
 * The comparison is wrapped in a `CASE` so every dialect answers with `1` or
 * `0`: PostgreSQL would otherwise return a boolean where MySQL and SQLite
 * return an integer.
 */
final readonly class ExistsInclude extends RelationAggregate
{
    #[\Override]
    protected function expression(string $key, string $table): string
    {
        return sprintf('CASE WHEN COUNT(%s) > 0 THEN 1 ELSE 0 END', $key);
    }
}
