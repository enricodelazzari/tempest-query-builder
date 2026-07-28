<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

use Exception;

/**
 * Thrown when an aggregate include is pointed at something the model does not
 * define as a relation.
 */
final class AggregateWasInvalid extends Exception implements QueryBuilderException
{
    public static function notARelation(string $model, string $relation): self
    {
        return new self(sprintf(
            'Aggregate include expects a relation, but `%s` does not define `%s` as one.',
            $model,
            $relation,
        ));
    }
}
