<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

final class InvalidSortQuery extends InvalidQuery
{
    protected const string SUBJECT = 'sort';
}
