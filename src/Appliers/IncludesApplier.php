<?php

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;

class IncludesApplier extends BaseApplier
{
    protected string $attribute = AllowedInclude::class;

    public function apply(): void
    {
        foreach ($this->items as $include) {

            $value = $this->request->get('includes') ?? null;

            if (is_null($value)) {
                continue;
            }

            $include->include->query($this->query, $include->name, $value);
        }
    }
}
