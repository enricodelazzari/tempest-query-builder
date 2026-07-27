<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;

/**
 * Binds a query builder to the database model it queries.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Model
{
    public function __construct(
        /** @var class-string|object */
        public string|object $model,
    ) {}
}
