<?php

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;

class QueryApplier
{
    private array $appliers = [
        FiltersApplier::class,
        IncludesApplier::class,
        SortsApplier::class,
        DefaultSortApplier::class,
    ];

    public function __construct(
        private ClassReflector $reflector,
        private Request $request,
        private SelectQueryBuilder $query
    ) {}

    public function apply(): void
    {
        foreach ($this->appliers as $applier) {
            new $applier(
                $this->reflector,
                $this->request,
                $this->query
            )->apply();
        }
    }
}
