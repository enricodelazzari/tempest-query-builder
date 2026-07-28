<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\ModelInspector;

/**
 * Implemented by a filter that works out its own column from the model, rather
 * than taking the one the attribute names.
 *
 * The column is resolved before the filter is applied, because a filter inside
 * a group is handed a `WhereGroupBuilder`, which keeps its model to itself.
 */
interface ResolvesColumn
{
    /**
     * @param  string  $column  the column the attribute resolved, which for a
     *                          filter of this kind names something else — a
     *                          relation, most likely
     */
    public function column(ModelInspector $model, string $column): string;
}
