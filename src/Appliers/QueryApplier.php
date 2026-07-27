<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;

/**
 * Runs every applier against the query, in the order the resulting SQL expects.
 */
final readonly class QueryApplier
{
    /** @var class-string<Applier>[] */
    private const array APPLIERS = [
        FiltersApplier::class,
        IncludesApplier::class,
        SortsApplier::class,
    ];

    public function __construct(
        private ClassReflector $reflector,
        private Request $request,
        private QueryBuilderConfig $config,
    ) {}

    public function apply(SelectQueryBuilder $query): void
    {
        foreach (self::APPLIERS as $applier) {
            new $applier($this->reflector, $this->request, $this->config)->apply($query);
        }
    }
}
