<?php

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;
use EnricoDeLazzari\QueryBuilder\Includes\Includes;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AllowedInclude
{
    public function __construct(
        public Includes $include,
        public string $name,
        public ?string $internalName = null
    ) {}
}
