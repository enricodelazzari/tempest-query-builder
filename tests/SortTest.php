<?php

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\DefaultSort;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Sorts\FieldSort;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;

it('can build query without any sort in request', function () {

    $request = RequestFactory::make([]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedSort(sort: new FieldSort, name: 'title', direction: 'desc')]
        // #[DefaultSort(sort: new FieldSort, name: 'title', direction: 'desc')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.author_id AS `books.author_id`, books.id AS `books.id`',
        'FROM `books`',
    ]));

    expect($query->getBindings())->toBe([]);
});

it('can build query with a default sort', function () {

    $request = RequestFactory::make([]);

    $query = new
        #[Model(name: Book::class)]
        #[DefaultSort(sort: new FieldSort, name: 'title', direction: 'desc')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.author_id AS `books.author_id`, books.id AS `books.id`',
        'FROM `books`',
        'ORDER BY title desc',
    ]));

    expect($query->getBindings())->toBe([]);
});

it('can build query with sort in request', function () {

    $request = RequestFactory::make([
        'sort' => 'title',
    ]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedSort(sort: new FieldSort, name: 'title', direction: 'desc')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.author_id AS `books.author_id`, books.id AS `books.id`',
        'FROM `books`',
        'ORDER BY title desc',
    ]));

    expect($query->getBindings())->toBe([]);
});
