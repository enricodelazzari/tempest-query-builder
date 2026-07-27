<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;
use Tempest\Support\Arr\ImmutableArray;

/**
 * Translates one part of the request into clauses on the query.
 */
abstract class Applier
{
    public function __construct(
        protected readonly ClassReflector $reflector,
        protected readonly Request $request,
        protected readonly QueryBuilderConfig $config,
    ) {}

    abstract public function apply(SelectQueryBuilder $query): void;

    /**
     * Reads a query parameter, unwrapping the immutable arrays Tempest returns
     * for nested values.
     */
    protected function parameter(string $name): mixed
    {
        $value = $this->request->get($name);

        return $value instanceof ImmutableArray ? $value->toArray() : $value;
    }

    /**
     * Splits a raw request value into a list, dropping empty entries.
     *
     * @return string[]
     */
    protected function split(mixed $value, ?string $delimiter = null): array
    {
        if ($value instanceof ImmutableArray) {
            $value = $value->toArray();
        }

        $values = is_array($value)
            ? $value
            : explode($delimiter ?? $this->config->delimiter, (string) $value);

        $values = array_map(
            static fn (mixed $item): string => trim((string) $item),
            $values,
        );

        return array_values(array_filter(
            $values,
            static fn (string $item): bool => $item !== '',
        ));
    }
}
