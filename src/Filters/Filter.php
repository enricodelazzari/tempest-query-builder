<?php

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

interface Filter
{
    public function query(
        SelectQueryBuilder $builder,
        string $attribute,
        string $value
    ): void;
}
