<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Filters;

use EnricoDeLazzari\QueryBuilder\Exceptions\ScopeWasInvalid;
use Tempest\Database\Builder\QueryBuilders\QueryScope;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

/**
 * Applies one of Tempest's query scopes, built from what the request asked for.
 *
 * A Tempest scope carries its values on its constructor rather than receiving
 * them when it runs, so the request value is passed there: `?filter[title]=a`
 * builds `new TheScope('a')`, and several values are spread, so
 * `?filter[id]=1,10` builds `new TheScope('1', '10')`.
 *
 * The scope decides which column it touches, so the name the filter is exposed
 * under — and any `alias` on it — has no bearing here.
 */
final readonly class ScopeFilter implements Filter
{
    /**
     * @param  class-string<QueryScope>  $scope
     */
    public function __construct(
        private string $scope,
    ) {
        if (! is_a($this->scope, QueryScope::class, allow_string: true)) {
            throw ScopeWasInvalid::notAQueryScope($this->scope);
        }
    }

    public function apply(SelectQueryBuilder $query, string $column, string|array $value): void
    {
        $query->applyScopes([
            new ($this->scope)(...(is_array($value) ? $value : [$value])),
        ]);
    }
}
