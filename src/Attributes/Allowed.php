<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

/**
 * Something the query builder lets the request ask for under a given name.
 */
interface Allowed
{
    /**
     * Name exposed in the query string.
     */
    public string $name {
        get;
    }
}
