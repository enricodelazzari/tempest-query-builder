<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use Exception;

/**
 * Thrown when a query builder is not bound to a usable database model.
 */
final class ModelWasInvalid extends Exception implements QueryBuilderException
{
    public static function missing(string $queryBuilder): self
    {
        return new self(sprintf(
            'Query builder `%s` is missing a `%s` attribute.',
            $queryBuilder,
            Model::class,
        ));
    }

    public static function notADatabaseModel(string $queryBuilder, string $model): self
    {
        return new self(sprintf(
            'Query builder `%s` is bound to `%s`, which is not a database model.',
            $queryBuilder,
            $model,
        ));
    }
}
