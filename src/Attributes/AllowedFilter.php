<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;
use EnricoDeLazzari\QueryBuilder\Filters\ExactFilter;
use EnricoDeLazzari\QueryBuilder\Filters\Filter;

/**
 * Allows `?filter[name]=…` to be applied to the query.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AllowedFilter implements Allowed
{
    /**
     * @param  non-empty-string|null  $delimiter
     * @param  string[]  $ignore
     */
    public function __construct(
        /**
         * Name exposed in the query string.
         */
        public string $name,

        /**
         * Strategy turning the request value into a `WHERE` clause.
         */
        public Filter $filter = new ExactFilter,

        /**
         * Model column to filter on, when it differs from the exposed name.
         */
        public ?string $alias = null,

        /**
         * Character used to split the value into multiple values. Falls back to
         * the delimiter configured in `QueryBuilderConfig`.
         */
        public ?string $delimiter = null,

        /**
         * Value applied when the request does not contain this filter.
         *
         * @var string|string[]|null
         */
        public string|array|null $default = null,

        /**
         * Values the filter refuses to act on. They are dropped from what the
         * request asked for; if nothing is left, the filter is not applied.
         *
         * @var string[]
         */
        public array $ignore = [],

        /**
         * Let an empty value match `NULL`, so `?filter[name]=` selects the rows
         * whose column is null instead of leaving the query untouched.
         */
        public bool $nullable = false,
    ) {}

    public function column(): string
    {
        return $this->alias ?? $this->name;
    }
}
