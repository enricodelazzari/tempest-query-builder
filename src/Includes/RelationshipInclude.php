<?php

namespace EnricoDeLazzari\QueryBuilder\Includes;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

class RelationshipInclude implements Includes
{
    public function query(
        SelectQueryBuilder $builder,
        string $relationship,
    ): void {
        $builder->with($relationship);
    }
}
