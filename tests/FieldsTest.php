<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedField;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFieldQuery;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;

it('selects every column when the request asks for no fields', function () {
    $query = new
        #[Model(Book::class)]
        #[AllowedField('title')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books`');
});

it('narrows the selection to a requested field', function () {
    $request = RequestFactory::make(['fields' => ['books' => 'title']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedField('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT books.title AS `books.title` FROM `books`');
});

it('narrows the selection to several fields, in the requested order', function () {
    $request = RequestFactory::make(['fields' => ['books' => 'title,id']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedField('id')]
        #[AllowedField('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT books.title AS `books.title`, books.id AS `books.id` FROM `books`',
    );
});

it('ignores a fieldset for another table', function () {
    $request = RequestFactory::make(['fields' => ['authors' => 'name']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedField('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books`');
});

it('leaves the columns of an included relation untouched', function () {
    $request = RequestFactory::make([
        'fields' => ['books' => 'title'],
        'include' => 'author',
    ]);

    $query = new
        #[Model(Book::class)]
        #[AllowedField('title')]
        #[AllowedInclude('author')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT books.title AS `books.title`, '.AUTHOR_FIELDS.' FROM `books` '.AUTHOR_JOIN,
    );
});

it('narrows the selection alongside a filter', function () {
    $request = RequestFactory::make([
        'fields' => ['books' => 'title'],
        'filter' => ['id' => '1'],
    ]);

    $query = new
        #[Model(Book::class)]
        #[AllowedField('title')]
        #[AllowedFilter('id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT books.title AS `books.title` FROM `books` WHERE `books`.`id` = ?',
    );
    expect($query->bindings)->toBe(['1']);
});

it('reads the fields parameter name from the config', function () {
    $request = RequestFactory::make(['only' => ['books' => 'title']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedField('title')]
        class($request, new QueryBuilderConfig(fieldsParameter: 'only'))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT books.title AS `books.title` FROM `books`');
});

it('rejects a field that was not allowed', function () {
    $request = RequestFactory::make(['fields' => ['books' => 'secret']]);

    new
        #[Model(Book::class)]
        #[AllowedField('title')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidFieldQuery::class, 'Field `secret` is not allowed. Allowed fields: `title`.');

it('rejects any field when the query builder allows none', function () {
    $request = RequestFactory::make(['fields' => ['books' => 'title']]);

    new
        #[Model(Book::class)]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidFieldQuery::class, 'Field `title` is not allowed. Allowed fields: none.');

it('ignores a field that was not allowed when strict mode is off', function () {
    $request = RequestFactory::make(['fields' => ['books' => 'secret,title']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedField('title')]
        class($request, new QueryBuilderConfig(strict: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT books.title AS `books.title` FROM `books`');
});

it('selects every column when no requested field could be applied', function () {
    $request = RequestFactory::make(['fields' => ['books' => 'secret']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedField('title')]
        class($request, new QueryBuilderConfig(strict: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books`');
});
