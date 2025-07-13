<?php

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;

#[Attribute()]
final readonly class Model
{
    public function __construct(
        public string|object $name,
    ) {}
}
