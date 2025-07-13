<?php

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\FieldDefinition;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

class PartialFilter implements Filter
{
    public function query(
        SelectQueryBuilder $builder,
        string $attribute,
        string $value
    ): void {

        // TODO

        $tableDefinition = (fn () => ($this->model))->call($builder)->getTableDefinition();

        $field = new FieldDefinition($tableDefinition, $attribute);

        // TODO: add bindings
        $builder->where("{$field} LIKE ?", "{$value}%");
    }
}
