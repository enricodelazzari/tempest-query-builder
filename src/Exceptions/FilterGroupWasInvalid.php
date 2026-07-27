<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use Exception;

/**
 * Thrown while reading a malformed filter group off a query builder, rather
 * than when a request eventually reaches it.
 */
final class FilterGroupWasInvalid extends Exception implements QueryBuilderException
{
    public static function withoutMembers(string $name): self
    {
        return new self(sprintf('Filter group `%s` has no members.', $name));
    }

    public static function withAForeignMember(string $name): self
    {
        return new self(sprintf(
            'Every member of filter group `%s` has to be an `%s`.',
            $name,
            AllowedFilter::class,
        ));
    }
}
