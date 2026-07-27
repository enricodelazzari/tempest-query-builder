<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

final class InvalidIncludeQuery extends InvalidQuery
{
    protected const string SUBJECT = 'include';
}
