<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Support;

use EnricoDeLazzari\QueryBuilder\Attributes\Allowed;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidQuery;
use Tempest\Support\Arr\ImmutableArray;

/**
 * Reads what the request asks for and checks it against what the query builder
 * allows.
 *
 * The using class is expected to hold a `$reflector`, a `$request` and a
 * `$config`.
 */
trait ReadsRequest
{
    /**
     * Reads the attributes of the given type off the query builder.
     *
     * @template TAllowed of Allowed
     *
     * @param  class-string<TAllowed>  $attribute
     * @return TAllowed[]
     */
    protected function allowed(string $attribute): array
    {
        return $this->reflector->getAttributes($attribute);
    }

    /**
     * Finds the attribute exposed under the given name.
     *
     * @template TAllowed of Allowed
     *
     * @param  TAllowed[]  $allowed
     * @return TAllowed|null
     */
    protected function find(array $allowed, string $name): ?Allowed
    {
        return array_find($allowed, static fn (Allowed $item): bool => $item->name === $name);
    }

    /**
     * Rejects requested names that were not allowed, unless strict mode is off.
     *
     * @param  string[]  $requested
     * @param  Allowed[]  $allowed
     * @param  class-string<InvalidQuery>  $exception
     */
    protected function guard(array $requested, array $allowed, string $exception): void
    {
        if (! $this->config->strict) {
            return;
        }

        $names = array_map(static fn (Allowed $item): string => $item->name, $allowed);
        $unknown = array_diff($requested, $names);

        if ($unknown !== []) {
            throw $exception::forNames($unknown, $names);
        }
    }

    /**
     * Reads a query parameter, unwrapping the immutable array Tempest returns
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
