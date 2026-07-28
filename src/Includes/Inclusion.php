<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Includes;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

interface Inclusion
{
    /**
     * @param  string  $relation  the model relation the include applies to
     * @param  string  $name  the name the include is exposed under
     */
    public function apply(SelectQueryBuilder $query, string $relation, string $name): void;
}
