<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

final class InvalidFilterQuery extends InvalidQuery
{
    protected const string SUBJECT = 'filter';
}
