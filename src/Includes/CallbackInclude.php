<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Includes;

use Closure;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

/**
 * Loads with a closure, for an include that does not earn a class of its own.
 *
 * PHP 8.5 allows a closure in an attribute, but only a static one that captures
 * nothing: an arrow function is rejected, because it captures its scope
 * implicitly.
 */
final readonly class CallbackInclude implements Inclusion
{
    /**
     * @param  Closure(SelectQueryBuilder, string, string): void  $callback
     */
    public function __construct(
        private Closure $callback,
    ) {}

    public function apply(SelectQueryBuilder $query, string $relation, string $name): void
    {
        ($this->callback)($query, $relation, $name);
    }
}
