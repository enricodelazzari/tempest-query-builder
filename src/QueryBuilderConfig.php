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
         * Character used to split a parameter into multiple values.
         */
        public string $delimiter = ',',

        /**
         * Throw an exception when the request asks for a filter, sort or include
         * that was not explicitly allowed. When disabled, unknown parameters are
         * silently ignored.
         */
        public bool $strict = true,
    ) {}
}
