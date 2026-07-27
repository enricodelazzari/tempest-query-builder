<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;
use Tempest\Database\Builder\WhereOperator;

/**
 * Compares a column using an arbitrary SQL operator, e.g. `?filter[pages]=100`
 * with `new OperatorFilter(WhereOperator::GREATER_THAN)`.
 */
final readonly class OperatorFilter implements Filter
{
    public function __construct(
        private WhereOperator $operator = WhereOperator::EQUALS,
    ) {}

    public function apply(SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void
    {
        $query->whereField($column, match (true) {
            $this->operator->supportsArray() => (array) $value,
            ! $this->operator->requiresValue() => null,
            // Operators taking a single value ignore any extra ones.
            is_array($value) => $value[0],
            default => $value,
        }, $this->operator);
    }
}
