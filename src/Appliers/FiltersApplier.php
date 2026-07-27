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
        $allowed = $this->allowed(AllowedFilter::class);
        $requested = $this->requested();

        $this->guard(array_keys($requested), $allowed, InvalidFilterQuery::class);

        foreach ($allowed as $filter) {
            $asked = array_key_exists($filter->name, $requested);

            $values = $this->split(
                ($asked ? $requested[$filter->name] : $filter->default) ?? '',
                $filter->delimiter,
            );

            if ($values === []) {
                // An empty value is a request to match null, but only for a
                // filter that opted into it.
                if ($asked && $filter->nullable) {
                    $query->whereNull($filter->column());
                }

                continue;
            }

            $values = array_values(array_diff($values, $filter->ignore));

            // Every value asked for was one the filter refuses to act on.
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

        if (! is_array($filters)) {
            return [];
        }

        $requested = [];

        // A query string can produce integer keys, e.g. `?filter[0]=x`.
        foreach ($filters as $name => $value) {
            $requested[(string) $name] = $value;
        }

        return $requested;
    }
}
