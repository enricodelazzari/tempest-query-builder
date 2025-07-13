<?php

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\Attributes\DefaultSort;

class DefaultSortApplier extends BaseApplier
{
    protected string $attribute = DefaultSort::class;

    public function apply(): void
    {
        $orderBy = (fn () => $this->select->orderBy)->call($this->query);

        if (count($orderBy) > 0) {
            return;
        }

        if (count($this->items) !== 1) {
            return;
        }

        $sort = $this->items[0];

        $sort->sort->query($this->query, $sort->name, $sort->direction);
    }
}
