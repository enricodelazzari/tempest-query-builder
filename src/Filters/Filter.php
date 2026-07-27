<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

interface Filter
{
    /**
     * @param  string  $column  the model column the filter applies to
     * @param  string|string[]  $value  the value(s) read from the request
     */
    public function apply(SelectQueryBuilder $query, string $column, string|array $value): void;
}
