<?php

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;
use EnricoDeLazzari\QueryBuilder\Sorts\Sort;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AllowedSort
{
    public function __construct(
        public Sort $sort,
        public string $name,
        public string $direction,
    ) {}
}
