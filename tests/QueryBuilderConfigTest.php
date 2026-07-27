<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Queries\BookQueryBuilder;
use Tempest\Http\Request;

it('reads the parameter names from the config', function () {
    $request = RequestFactory::make([
        'where' => ['title' => 'tempest'],
        'order' => '-title',
        'with' => 'author',
    ]);

    $config = new QueryBuilderConfig(
        filterParameter: 'where',
        sortParameter: 'order',
        includeParameter: 'with',
    );

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        #[AllowedSort('title')]
        #[AllowedInclude('author')]
        class($request, $config)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(implode(' ', [
        'SELECT '.BOOK_FIELDS.', '.AUTHOR_FIELDS,
        'FROM `books`',
        AUTHOR_JOIN,
        'WHERE `books`.`title` = ?',
        'ORDER BY `books`.`title` DESC',
    ]));

    expect($query->bindings)->toBe(['tempest']);
});

it('reads the delimiter from the config', function () {
    $request = RequestFactory::make(['filter' => ['id' => '1|2']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        class($request, new QueryBuilderConfig(delimiter: '|'))
        {
            use HasQueryBuilder;
        };

    expect($query->bindings)->toBe(['1', '2']);
});

it('is resolved from the container', function () {
    $this->container->config(new QueryBuilderConfig(filterParameter: 'where'));
    $this->container->singleton(
        Request::class,
        fn () => RequestFactory::make(['where' => ['title' => 'tempest']]),
    );

    $query = $this->container->get(
        BookQueryBuilder::class,
    );

    expect($query->bindings)->toBe(['%tempest%']);
});
