<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;

/**
 * Base for filters matching a column with `LIKE`. Several values are combined
 * with `OR` inside a group, so `?filter[title]=a,b` matches either of them.
 */
abstract class LikeFilter implements Filter
{
    public function apply(SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void
    {
        if (! is_array($value)) {
            $query->whereLike($column, $this->pattern($value));

            return;
        }

        $query->andWhereGroup(function (WhereGroupBuilder $group) use ($column, $value): void {
            foreach ($value as $item) {
                $group->orWhereLike($column, $this->pattern($item));
            }
        });
    }

    /**
     * Turns a request value into the `LIKE` pattern to match against.
     */
    abstract protected function pattern(string $value): string;
}
