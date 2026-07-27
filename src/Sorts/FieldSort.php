<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Sorts;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Direction;

/**
 * Orders the results by one of the model's own columns.
 */
final class FieldSort implements Sort
{
    public function apply(SelectQueryBuilder $query, string $column, Direction $direction): void
    {
        // Columns are qualified with the table name, otherwise an include that
        // joins a table sharing a column name makes the statement ambiguous.
        $field = str_contains($column, '.')
            ? $column
            : "{$query->model->getTableName()}.{$column}";

        $query->orderBy($field, $direction);
    }
}
