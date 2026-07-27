<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilterGroup;
use EnricoDeLazzari\QueryBuilder\Attributes\Conjunction;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFilterQuery;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;

final class FiltersApplier extends Applier
{
    public function apply(SelectQueryBuilder $query): void
    {
        $filters = $this->allowed(AllowedFilter::class);
        $groups = $this->allowed(AllowedFilterGroup::class);
        $requested = $this->requested();

        $this->guard(array_keys($requested), [...$filters, ...$groups], InvalidFilterQuery::class);

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter, $requested);
        }

        foreach ($groups as $group) {
            if (! array_key_exists($group->name, $requested)) {
                continue;
            }

            $this->applyGroup($query, $group, $requested[$group->name]);
        }
    }

    /**
     * @param  array<string, mixed>  $requested
     */
    private function applyFilter(SelectQueryBuilder $query, AllowedFilter $filter, array $requested): void
    {
        $asked = array_key_exists($filter->name, $requested);

        $values = $this->split(
            ($asked ? $requested[$filter->name] : $filter->default) ?? '',
            $filter->delimiter,
        );

        if ($values === []) {
            // An empty value is a request to match null, but only for a filter
            // that opted into it.
            if ($asked && $filter->nullable) {
                $query->whereNull($filter->column());
            }

            return;
        }

        $values = $this->keep($values, $filter);

        // Every value asked for was one the filter refuses to act on.
        if ($values === []) {
            return;
        }

        $filter->filter->apply($query, $filter->column(), $this->one($values));
    }

    /**
     * Hands the group's value to each of its members, joining them with the
     * group's conjunction, and the group itself to the rest of the query with
     * `AND`.
     */
    private function applyGroup(SelectQueryBuilder $query, AllowedFilterGroup $group, mixed $raw): void
    {
        /** @var array<array{AllowedFilter, string|string[]}> $members */
        $members = [];

        foreach ($group->members as $member) {
            $values = $this->keep($this->split($raw ?? '', $member->delimiter), $member);

            if ($values !== []) {
                $members[] = [$member, $this->one($values)];
            }
        }

        if ($members === []) {
            return;
        }

        $query->andWhereGroup(function (WhereGroupBuilder $builder) use ($members, $group): void {
            foreach ($members as $index => [$member, $value]) {
                // Each member gets a nesting of its own, which is what makes a
                // member free to write more than one condition without the
                // group's conjunction binding to only part of it.
                $builder->whereGroup(
                    static fn (WhereGroupBuilder $condition) => $member->filter->apply(
                        $condition,
                        $member->column(),
                        $value,
                    ),
                    $index === 0 ? Conjunction::AND->value : $group->conjunction->value,
                );
            }
        });
    }

    /**
     * @param  string[]  $values
     * @return string[]
     */
    private function keep(array $values, AllowedFilter $filter): array
    {
        return array_values(array_diff($values, $filter->ignore));
    }

    /**
     * A lone value is handed over as a string, so a filter can tell one from
     * many without counting.
     *
     * @param  string[]  $values
     * @return string|string[]
     */
    private function one(array $values): string|array
    {
        return count($values) === 1 ? array_first($values) : $values;
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
