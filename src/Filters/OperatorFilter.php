<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\WhereOperator;

/**
 * Compares a column using an arbitrary SQL operator, e.g. `?filter[pages]=100`
 * with `new OperatorFilter(WhereOperator::GREATER_THAN)`.
 */
final class OperatorFilter implements Filter
{
    public function __construct(
        private readonly WhereOperator $operator = WhereOperator::EQUALS,
    ) {}

    public function apply(SelectQueryBuilder $query, string $column, string|array $value): void
    {
        if ($this->operator->supportsArray()) {
            $query->whereField($column, (array) $value, $this->operator);

            return;
        }

        if (! $this->operator->requiresValue()) {
            $query->whereField($column, null, $this->operator);

            return;
        }

        $query->whereField($column, is_array($value) ? reset($value) : $value, $this->operator);
    }
}
