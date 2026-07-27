<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Sorts;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Direction;

interface Sort
{
    /**
     * @param  string  $column  the model column the sort applies to
     */
    public function apply(SelectQueryBuilder $query, string $column, Direction $direction): void;
}
