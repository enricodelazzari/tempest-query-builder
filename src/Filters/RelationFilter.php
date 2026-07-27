<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;

/**
 * Applies a filter to a related model through a `WHERE EXISTS` subquery.
 *
 * The column the inner filter receives is the one resolved by the attribute, so
 * `#[AllowedFilter(name: 'author', alias: 'name', filter: new RelationFilter('author'))]`
 * turns `?filter[author]=tolkien` into `EXISTS (… WHERE authors.name = ?)`.
 */
final readonly class RelationFilter implements Filter
{
    public function __construct(
        private string $relation,
        private Filter $filter = new ExactFilter,
    ) {}

    public function apply(SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void
    {
        $query->whereHas(
            $this->relation,
            fn (SelectQueryBuilder $related) => $this->filter->apply($related, $column, $value),
        );
    }
}
