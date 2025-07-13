<?php

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;
use EnricoDeLazzari\QueryBuilder\Filters\Filter;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AllowedFilter
{
    public function __construct(
        public Filter $filter,
        public string $name,
        public ?string $internalName = null,
        public string $delimiter = ',',
    ) {}
}
