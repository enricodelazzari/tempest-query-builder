<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use EnricoDeLazzari\QueryBuilder\Support\ReadsRequest;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;

/**
 * Translates one part of the request into clauses on the query.
 */
abstract class Applier
{
    use ReadsRequest;

    public function __construct(
        protected readonly ClassReflector $reflector,
        protected readonly Request $request,
        protected readonly QueryBuilderConfig $config,
    ) {}

    abstract public function apply(SelectQueryBuilder $query): void;
}
