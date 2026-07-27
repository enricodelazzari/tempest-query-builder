<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

final class InvalidFieldQuery extends InvalidQuery
{
    protected const string SUBJECT = 'field';
}
