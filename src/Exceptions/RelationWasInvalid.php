<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

use Exception;

/**
 * Thrown when a filter is pointed at a relation the model does not define, or
 * defines as something else.
 */
final class RelationWasInvalid extends Exception implements QueryBuilderException
{
    public static function notABelongsTo(string $model, string $relation): self
    {
        return new self(sprintf(
            'Belongs to filter expects a `belongsTo` relation, but `%s` does not define `%s` as one.',
            $model,
            $relation,
        ));
    }
}
