<?php

namespace EnricoDeLazzari\QueryBuilder\Sorts;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

interface Sort
{
    public function query(
        SelectQueryBuilder $builder,
        string $attribute,
        string $direction,
    ): void;
}
