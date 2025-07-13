<?php

namespace EnricoDeLazzari\QueryBuilder\Sorts;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

class FieldSort implements Sort
{
    public function query(
        SelectQueryBuilder $builder,
        string $attribute,
        string $direction,
    ): void {
        $builder->orderBy("{$attribute} {$direction}");
    }
}
