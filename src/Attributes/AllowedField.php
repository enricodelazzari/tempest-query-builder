<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;

/**
 * Allows `?fields[<table>]=name` to narrow the columns the query selects.
 *
 * The name is the model's column name: unlike filters and sorts, a field cannot
 * be exposed under a different name, because the column is what the result is
 * keyed by when the model is hydrated.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AllowedField implements Allowed
{
    public function __construct(
        public string $name,
    ) {}
}
