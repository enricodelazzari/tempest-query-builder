<?php

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;

class FiltersApplier extends BaseApplier
{
    protected string $attribute = AllowedFilter::class;

    public function apply(): void
    {
        foreach ($this->items as $filter) {

            $value = $this->request->get('filter')[$filter->name] ?? null;

            if (is_null($value)) {
                continue;
            }

            $filter->filter->query($this->query, $filter->name, $value);
        }
    }
}
