<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;

/**
 * Matches a column against an exact value, or against a set of values when
 * the request contains several of them: `?filter[id]=1,2` becomes `id IN (?, ?)`.
 */
final class ExactFilter implements Filter
{
    public function apply(SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void
    {
        if (is_array($value)) {
            $query->whereIn($column, $value);

            return;
        }

        $query->whereField($column, $value);
    }
}
