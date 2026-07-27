<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;
use EnricoDeLazzari\QueryBuilder\Sorts\FieldSort;
use EnricoDeLazzari\QueryBuilder\Sorts\Sort;
use Tempest\Database\Direction;

/**
 * Orders the query when the request does not ask for a sort. Repeat the
 * attribute to order by several columns.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class DefaultSort
{
    public function __construct(
        /**
         * Model column to sort on.
         */
        public string $name,

        public Direction $direction = Direction::ASC,

        /**
         * Strategy turning the value into an `ORDER BY` clause.
         */
        public Sort $sort = new FieldSort,
    ) {}
}
