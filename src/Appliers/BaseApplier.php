<?php

namespace EnricoDeLazzari\QueryBuilder\Appliers;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;

abstract class BaseApplier
{
    protected string $attribute;

    protected array $items;

    public function __construct(
        ClassReflector $reflector,
        protected Request $request,
        protected SelectQueryBuilder $query
    ) {
        $this->items = $reflector->getAttributes(
            $this->attribute
        );
    }

    abstract public function apply(): void;
}
