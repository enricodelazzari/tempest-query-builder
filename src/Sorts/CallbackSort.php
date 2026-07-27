<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Sorts;

use Closure;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Direction;

/**
 * Orders with a closure, for an ordering that does not earn a class of its own.
 *
 * PHP 8.5 allows a closure in an attribute, but only a static one that captures
 * nothing: an arrow function is rejected, because it captures its scope
 * implicitly.
 */
final readonly class CallbackSort implements Sort
{
    /**
     * @param  Closure(SelectQueryBuilder, string, Direction): void  $callback
     */
    public function __construct(
        private Closure $callback,
    ) {}

    public function apply(SelectQueryBuilder $query, string $column, Direction $direction): void
    {
        ($this->callback)($query, $column, $direction);
    }
}
