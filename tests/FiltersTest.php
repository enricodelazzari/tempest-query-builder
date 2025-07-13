<?php

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Filters\ExactFilter;
use EnricoDeLazzari\QueryBuilder\Filters\PartialFilter;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;

it('can build query without filters in request', function () {

    $request = RequestFactory::make([]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedFilter(filter: new ExactFilter, name: 'id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.id AS `books.id`',
        'FROM `books`',
    ]));

    expect($query->getBindings())->toBe([]);
});

it('can build query with an exact filter', function () {

    $request = RequestFactory::make([
        'filter' => [
            'id' => '1',
        ],
    ]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedFilter(filter: new ExactFilter, name: 'id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.id AS `books.id`',
        'FROM `books`',
        'WHERE `books`.`id` = ?',
    ]));

    expect($query->getBindings())->toBe(['1']);
});

it('can build query with an exact filter with comma separated values', function () {

    $request = RequestFactory::make([
        'filter' => [
            'id' => '1,2',
        ],
    ]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedFilter(filter: new ExactFilter, name: 'id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.id AS `books.id`',
        'FROM `books`',
        'WHERE `books`.`id` IN (?,?)',
    ]));

    expect($query->getBindings())->toBe(['1', '2']);
});

it('can build query with multiple exact filters', function () {

    $request = RequestFactory::make([
        'filter' => [
            'id' => '1',
            'title' => 'test',
        ],
    ]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedFilter(filter: new ExactFilter, name: 'id')]
        #[AllowedFilter(filter: new ExactFilter, name: 'title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.id AS `books.id`',
        'FROM `books`',
        'WHERE `books`.`id` = ?',
        'AND `books`.`title` = ?',
    ]));

    expect($query->getBindings())->toBe(['1', 'test']);
});

it('can build query with a partial filter', function () {

    $request = RequestFactory::make([
        'filter' => [
            'title' => 'test',
        ],
    ]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedFilter(filter: new PartialFilter, name: 'title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.id AS `books.id`',
        'FROM `books`',
        'WHERE `books`.`title` LIKE ?',
    ]));

    expect($query->getBindings())->toBe(['test%']);
});
