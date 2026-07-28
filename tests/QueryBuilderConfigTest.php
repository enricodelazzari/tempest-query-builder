<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFilterQuery;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidIncludeQuery;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Queries\BookQueryBuilder;
use Tempest\Http\Request;

it('reads the parameter names from the config', function (): void {
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
        'FROM books',
        AUTHOR_JOIN,
        'WHERE books.title = ?',
        'ORDER BY books.title DESC',
    ]));

    expect($query->bindings)->toBe(['tempest']);
});

it('reads the delimiter from the config', function (): void {
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

it('is resolved from the container', function (): void {
    $this->container->config(new QueryBuilderConfig(filterParameter: 'where'));
    $this->container->singleton(
        Request::class,
        fn (): \Tempest\Http\GenericRequest => RequestFactory::make(['where' => ['title' => 'tempest']]),
    );

    $query = $this->container->get(
        BookQueryBuilder::class,
    );

    expect($query->bindings)->toBe(['%tempest%']);
});

it('overrides strict mode for one kind of parameter', function (): void {
    $request = RequestFactory::make([
        'filter' => ['secret' => 'x'],
        'sort' => 'secret',
    ]);

    // Strict overall, but sorts are told to keep quiet.
    $config = new QueryBuilderConfig(strict: true, strictSorts: false);

    expect(fn (): object => new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        #[AllowedSort('title')]
        class($request, $config)
        {
            use HasQueryBuilder;
        })->toThrow(InvalidFilterQuery::class);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        class(RequestFactory::make(['sort' => 'secret']), $config)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books');
});

it('rejects an unknown parameter of one kind while ignoring another', function (): void {
    $request = RequestFactory::make(['include' => 'secret']);

    $config = new QueryBuilderConfig(strict: false, strictIncludes: true);

    new
        #[Model(Book::class)]
        #[AllowedInclude('author')]
        class($request, $config)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidIncludeQuery::class);

it('keeps filter values whole when splitting is turned off', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'Tolkien, J.R.R.']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        class($request, new QueryBuilderConfig(splitFilterValues: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title = ?')
        ->and($query->bindings)->toBe(['Tolkien, J.R.R.']);
});

it('keeps every value whole when the delimiter is empty', function (): void {
    $request = RequestFactory::make([
        'filter' => ['title' => 'a,b'],
        'sort' => '-title',
    ]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        #[AllowedSort('title')]
        class($request, new QueryBuilderConfig(delimiter: ''))
        {
            use HasQueryBuilder;
        };

    expect($query->bindings)->toBe(['a,b']);
});

it('still splits sorts when only filter splitting is off', function (): void {
    $request = RequestFactory::make(['sort' => 'title,-id']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        #[AllowedSort('id')]
        class($request, new QueryBuilderConfig(splitFilterValues: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT '.BOOK_FIELDS.' FROM books ORDER BY books.title ASC, books.id DESC',
    );
});
