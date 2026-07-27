<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

/**
 * How the members of a filter group are joined to each other.
 */
enum Conjunction: string
{
    case AND = 'AND';
    case OR = 'OR';
}
