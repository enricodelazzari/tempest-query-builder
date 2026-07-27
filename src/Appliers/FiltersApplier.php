<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFilterQuery;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

final class FiltersApplier extends Applier
{
    public function apply(SelectQueryBuilder $query): void
    {
        /** @var AllowedFilter[] $allowed */
        $allowed = $this->reflector->getAttributes(AllowedFilter::class);

        $requested = $this->requested();

        $this->guard($requested, $allowed);

        foreach ($allowed as $filter) {
            $value = $requested[$filter->name] ?? $filter->default;

            if ($value === null) {
                continue;
            }

            $values = $this->split($value, $filter->delimiter);

            if ($values === []) {
                continue;
            }

            $filter->filter->apply(
                $query,
                $filter->column(),
                count($values) === 1 ? $values[0] : $values,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requested(): array
    {
        $filters = $this->parameter($this->config->filterParameter);

        return is_array($filters) ? $filters : [];
    }

    /**
     * @param  array<string, mixed>  $requested
     * @param  AllowedFilter[]  $allowed
     */
    private function guard(array $requested, array $allowed): void
    {
        if (! $this->config->strict) {
            return;
        }

        $names = array_map(static fn (AllowedFilter $filter): string => $filter->name, $allowed);
        $unknown = array_diff(array_keys($requested), $names);

        if ($unknown !== []) {
            throw InvalidFilterQuery::forNames($unknown, $names);
        }
    }
}
