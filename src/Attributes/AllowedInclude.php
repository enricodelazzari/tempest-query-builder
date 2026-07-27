<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;
use EnricoDeLazzari\QueryBuilder\Includes\Inclusion;
use EnricoDeLazzari\QueryBuilder\Includes\RelationshipInclude;

/**
 * Allows `?include=name` to eager load a relation on the query.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AllowedInclude
{
    public function __construct(
        /**
         * Name exposed in the query string.
         */
        public string $name,

        /**
         * Strategy loading the relation.
         */
        public Inclusion $include = new RelationshipInclude,

        /**
         * Model relation to load, when it differs from the exposed name.
         */
        public ?string $alias = null,
    ) {}

    public function relation(): string
    {
        return $this->alias ?? $this->name;
    }
}
