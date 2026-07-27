<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;
use EnricoDeLazzari\QueryBuilder\Sorts\FieldSort;
use EnricoDeLazzari\QueryBuilder\Sorts\Sort;

/**
 * Allows `?sort=name` (ascending) and `?sort=-name` (descending) to be applied
 * to the query.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AllowedSort implements Allowed
{
    public function __construct(
        /**
         * Name exposed in the query string.
         */
        public string $name,

        /**
         * Strategy turning the request value into an `ORDER BY` clause.
         */
        public Sort $sort = new FieldSort,

        /**
         * Model column to sort on, when it differs from the exposed name.
         */
        public ?string $alias = null,
    ) {}

    public function column(): string
    {
        return $this->alias ?? $this->name;
    }
}
