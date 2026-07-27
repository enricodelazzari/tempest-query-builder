<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Scopes;

use Tempest\Database\Builder\QueryBuilders\QueryScope;
use Tempest\Database\Builder\QueryBuilders\SupportsWhereStatements;
use Tempest\Database\Builder\WhereOperator;

final readonly class IdBetween implements QueryScope
{
    public function __construct(
        private string $min,
        private string $max,
    ) {}

    public function apply(SupportsWhereStatements $builder): void
    {
        $builder->whereField('id', [$this->min, $this->max], WhereOperator::BETWEEN);
    }
}
