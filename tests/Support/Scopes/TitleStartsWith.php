<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Scopes;

use Tempest\Database\Builder\QueryBuilders\QueryScope;
use Tempest\Database\Builder\QueryBuilders\SupportsWhereStatements;
use Tempest\Database\Builder\WhereOperator;

final readonly class TitleStartsWith implements QueryScope
{
    public function __construct(
        private string $prefix,
    ) {}

    public function apply(SupportsWhereStatements $builder): void
    {
        $builder->whereField('title', "{$this->prefix}%", WhereOperator::LIKE);
    }
}
