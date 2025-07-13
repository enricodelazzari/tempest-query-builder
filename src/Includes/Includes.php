<?php

namespace EnricoDeLazzari\QueryBuilder\Includes;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

interface Includes
{
    public function query(
        SelectQueryBuilder $builder,
        string $relationship,
    ): void;
}
