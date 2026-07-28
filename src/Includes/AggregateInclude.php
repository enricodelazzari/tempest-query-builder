<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Includes;

use Tempest\Database\AggregateFunction;

/**
 * Aggregates a column of a relation, e.g. `?include=pagesSum` with
 * `new AggregateInclude(AggregateFunction::SUM, 'pages')`.
 *
 * Unlike a count, this is `null` when the relation has no records — the same
 * answer SQL gives for an aggregate over no rows.
 */
final readonly class AggregateInclude extends RelationAggregate
{
    public function __construct(
        private AggregateFunction $function,

        /**
         * Column of the related table to aggregate.
         */
        private string $column,
    ) {}

    #[\Override]
    protected function expression(string $key, string $table): string
    {
        return sprintf('%s(`%s`.`%s`)', $this->function->value, $table, $this->column);
    }
}
