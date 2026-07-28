<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder;

/**
 * Controls how query builders read the incoming request.
 *
 * Publish a `query-builder.config.php` file returning an instance of this class
 * to override the defaults application-wide.
 */
final class QueryBuilderConfig
{
    public function __construct(
        /**
         * Name of the query parameter holding the filters, e.g. `?filter[title]=tempest`.
         */
        public string $filterParameter = 'filter',

        /**
         * Name of the query parameter holding the sorts, e.g. `?sort=-title`.
         */
        public string $sortParameter = 'sort',

        /**
         * Name of the query parameter holding the includes, e.g. `?include=author`.
         */
        public string $includeParameter = 'include',

        /**
         * Name of the query parameter holding the fields, e.g. `?fields[books]=title`.
         */
        public string $fieldsParameter = 'fields',

        /**
         * Character used to split a parameter into multiple values. An empty
         * string keeps every value whole, for a query string whose values are
         * expected to contain the delimiter themselves.
         */
        public string $delimiter = ',',

        /**
         * Split filter values on the delimiter. Disabling this leaves filters
         * with whole values while sorts, includes and fields keep splitting.
         */
        public bool $splitFilterValues = true,

        /**
         * Throw an exception when the request asks for a filter, sort, include
         * or field that was not explicitly allowed. When disabled, unknown
         * parameters are silently ignored.
         */
        public bool $strict = true,

        /**
         * Overrides `$strict` for one kind of parameter, e.g. to reject an
         * unknown filter while ignoring an unknown sort.
         */
        public ?bool $strictFilters = null,

        public ?bool $strictSorts = null,

        public ?bool $strictIncludes = null,

        public ?bool $strictFields = null,
    ) {}
}
