<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

use Exception;
use Tempest\Database\Builder\QueryBuilders\QueryScope;

/**
 * Thrown when a scope filter is pointed at something that is not a query scope.
 */
final class ScopeWasInvalid extends Exception implements QueryBuilderException
{
    public static function notUsableInAGroup(string $scope): self
    {
        return new self(sprintf(
            'Scope `%s` cannot be a member of a filter group: Tempest applies scopes to a whole query, not to a group of conditions. Allow it as a filter of its own instead.',
            $scope,
        ));
    }

    public static function notAQueryScope(string $scope): self
    {
        return new self(sprintf(
            'Scope filter expects a `%s`, but `%s` is not one.',
            QueryScope::class,
            $scope,
        ));
    }
}
