<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Queries;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedField;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\DefaultSort;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Filters\PartialFilter;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use Tempest\Database\Direction;

#[Model(Book::class)]
#[AllowedFilter('title', new PartialFilter)]
#[AllowedFilter('author_id')]
#[AllowedFilter('id')]
#[AllowedInclude('author')]
#[AllowedField('id')]
#[AllowedField('title')]
#[AllowedSort('title')]
#[AllowedSort('id')]
#[DefaultSort('id', Direction::DESC)]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
