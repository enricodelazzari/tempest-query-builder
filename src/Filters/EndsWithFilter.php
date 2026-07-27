<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;

/**
 * Matches a column against a `LIKE %value` pattern.
 */
final class EndsWithFilter implements Filter
{
    public function apply(SelectQueryBuilder $query, string $column, string|array $value): void
    {
        if (! is_array($value)) {
            $query->whereLike($column, "%{$value}");

            return;
        }

        $query->whereGroup(function (WhereGroupBuilder $group) use ($column, $value): void {
            foreach ($value as $item) {
                $group->orWhereLike($column, "%{$item}");
            }
        });
    }
}
