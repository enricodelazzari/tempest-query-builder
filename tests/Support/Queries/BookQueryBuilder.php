<?php

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Queries;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\DefaultSort;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Filters\ExactFilter;
use EnricoDeLazzari\QueryBuilder\Filters\PartialFilter;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Includes\RelationshipInclude;
use EnricoDeLazzari\QueryBuilder\Sorts\FieldSort;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;

#[Model(name: Book::class)]
#[AllowedFilter(filter: new PartialFilter, name: 'title')]
#[AllowedFilter(filter: new ExactFilter, name: 'author_id')]
#[AllowedFilter(filter: new ExactFilter, name: 'id')]
#[AllowedInclude(include: new RelationshipInclude, name: 'author')]
#[AllowedSort(sort: new FieldSort, name: 'title', direction: 'desc')]
#[DefaultSort(sort: new FieldSort, name: 'id', direction: 'desc')]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
