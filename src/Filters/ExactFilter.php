<?php

namespace EnricoDeLazzari\QueryBuilder\Filters;

use Tempest\Database\Builder\FieldDefinition;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

class ExactFilter implements Filter
{
    public function query(
        SelectQueryBuilder $builder,
        string $attribute,
        string $value
    ): void {
        $model = (fn () => $this->model ?? [])->call($builder);
        $where = (fn () => $this->select->where)->call($builder) ?? [];
        $values = explode(',', $value);

        $field = new FieldDefinition(
            $model->getTableDefinition(),
            $attribute,
        );

        $and = count($where) > 0 ? 'AND ' : '';

        if (count($values) === 1) {
            $builder->where("{$and}{$field} = ?", $value);

            return;
        }

        $placeholders = array_fill(0, count($values), '?');
        $placeholders = implode(',', $placeholders);

        $builder->where("{$and}{$field} IN ({$placeholders})", ...$values);
    }
}
