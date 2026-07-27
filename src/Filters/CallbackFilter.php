<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Closure;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;

/**
 * Filters with a closure, for a condition that does not earn a class of its own.
 *
 * PHP 8.5 allows a closure in an attribute, but only a static one that captures
 * nothing: an arrow function is rejected, because it captures its scope
 * implicitly.
 */
final readonly class CallbackFilter implements Filter
{
    /**
     * @param  Closure(SelectQueryBuilder|WhereGroupBuilder, string, string|string[]): void  $callback
     */
    public function __construct(
        private Closure $callback,
    ) {}

    public function apply(SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void
    {
        ($this->callback)($query, $column, $value);
    }
}
