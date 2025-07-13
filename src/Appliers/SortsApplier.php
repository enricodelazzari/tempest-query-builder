<?php

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;

class SortsApplier
{
    private array $sorts;

    public function __construct(
        ClassReflector $reflector,
        private Request $request,
        private SelectQueryBuilder $query
    ) {
        // TODO: check duplicates
        $this->sorts = $reflector->getAttributes(AllowedSort::class);
    }

    public function apply(): void
    {
        foreach ($this->sorts as $sort) {

            $value = $this->request->get('sort') ?? null;

            if ($sort->name !== $value) {
                continue;
            }

            $sort->sort->query($this->query, $sort->name, $sort->direction);
        }
    }
}
