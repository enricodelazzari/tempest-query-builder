<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

final class InvalidSortQuery extends InvalidQuery
{
    protected static function subject(): string
    {
        return 'sorts';
    }
}
