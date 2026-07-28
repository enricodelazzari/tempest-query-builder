<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use EnricoDeLazzari\QueryBuilder\Exceptions\RelationWasInvalid;
use Tempest\Database\BelongsTo;
use Tempest\Database\Builder\ModelInspector;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;

/**
 * Matches a `belongsTo` relation by the key of the record it points at, e.g.
 * `?filter[author]=1` on a book, without naming the foreign key.
 *
 * The point of it over a plain exact filter is that the foreign key is read off
 * the relation, so a model that names it something other than the convention
 * still filters correctly, and a relation that does not exist is reported
 * instead of reaching the database as an unknown column.
 */
final readonly class BelongsToFilter implements Filter, ResolvesColumn
{
    public function __construct(
        private Filter $filter = new ExactFilter,
    ) {}

    public function column(ModelInspector $model, string $column): string
    {
        $relation = $model->getRelation($column);

        if (! $relation instanceof BelongsTo) {
            throw RelationWasInvalid::notABelongsTo($model->getName(), $column);
        }

        return $relation->getOwnerFieldName();
    }

    public function apply(SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void
    {
        $this->filter->apply($query, $column, $value);
    }
}
