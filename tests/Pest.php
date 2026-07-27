<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Tests\IntegrationTestCase;

pest()
    ->extend(IntegrationTestCase::class)
    ->in(__DIR__);

/**
 * The columns every `Book` query selects, before any include is applied.
 */
const BOOK_FIELDS = 'books.id AS `books.id`, books.title AS `books.title`, books.author_id AS `books.author_id`';

/**
 * The columns an eager loaded `author` adds to the selection.
 */
const AUTHOR_FIELDS = 'authors.id AS `author.id`, authors.name AS `author.name`';

const AUTHOR_JOIN = 'LEFT JOIN authors ON authors.id = books.author_id';

/**
 * Compiles a query builder to its SQL statement.
 */
function sql(object $query): string
{
    return $query->compile()->toString();
}
