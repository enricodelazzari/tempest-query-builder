<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidIncludeQuery;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Includes\RelationshipInclude;
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;

it('loads nothing when the request has no includes', function () {
    $query = new
        #[Model(Book::class)]
        #[AllowedInclude('author')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books`');
});

it('eager loads a relation', function () {
    $request = RequestFactory::make(['include' => 'author']);

    $query = new
        #[Model(Book::class)]
        #[AllowedInclude('author', new RelationshipInclude)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT '.BOOK_FIELDS.', '.AUTHOR_FIELDS.' FROM `books` '.AUTHOR_JOIN,
    );
});

it('eager loads a relation exposed under another name', function () {
    $request = RequestFactory::make(['include' => 'writer']);

    $query = new
        #[Model(Book::class)]
        #[AllowedInclude('writer', alias: 'author')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT '.BOOK_FIELDS.', '.AUTHOR_FIELDS.' FROM `books` '.AUTHOR_JOIN,
    );
});

it('eager loads several relations', function () {
    $request = RequestFactory::make(['include' => 'author,author.books']);

    $query = new
        #[Model(Book::class)]
        #[AllowedInclude('author')]
        #[AllowedInclude('author.books')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(implode(' ', [
        'SELECT '.BOOK_FIELDS.', '.AUTHOR_FIELDS.',',
        'author_books.id AS `author.books.id`,',
        'author_books.title AS `author.books.title`,',
        'author_books.author_id AS `author.books.author_id`',
        'FROM `books`',
        AUTHOR_JOIN,
        'LEFT JOIN books AS author_books ON author_books.author_id = authors.id',
    ]));
});

it('rejects an include that was not allowed', function () {
    $request = RequestFactory::make(['include' => 'secret']);

    new
        #[Model(Book::class)]
        #[AllowedInclude('author')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidIncludeQuery::class, 'Include `secret` is not allowed. Allowed includes: `author`.');

it('ignores an include that was not allowed when strict mode is off', function () {
    $request = RequestFactory::make(['include' => 'secret,author']);

    $query = new
        #[Model(Book::class)]
        #[AllowedInclude('author')]
        class($request, new QueryBuilderConfig(strict: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT '.BOOK_FIELDS.', '.AUTHOR_FIELDS.' FROM `books` '.AUTHOR_JOIN,
    );
});
