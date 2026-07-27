<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

/**
 * Matches a column against a `LIKE %value` pattern.
 */
final class EndsWithFilter extends LikeFilter
{
    protected function pattern(string $value): string
    {
        return "%{$value}";
    }
}
