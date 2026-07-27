<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Includes;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

/**
 * Eager loads a relation defined on the model.
 */
final class RelationshipInclude implements Inclusion
{
    public function apply(SelectQueryBuilder $query, string $relation): void
    {
        $query->with($relation);
    }
}
