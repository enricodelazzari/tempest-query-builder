<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilterGroup;
use EnricoDeLazzari\QueryBuilder\Attributes\Conjunction;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\FilterGroupWasInvalid;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFilterQuery;
use EnricoDeLazzari\QueryBuilder\Exceptions\ScopeWasInvalid;
use EnricoDeLazzari\QueryBuilder\Filters\PartialFilter;
use EnricoDeLazzari\QueryBuilder\Filters\ScopeFilter;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Scopes\TitleStartsWith;

it('leaves the query alone when the group is not asked for', function (): void {
    $query = new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [new AllowedFilter('title', new PartialFilter)])]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books');
});

it('hands one value to every member, joined with or', function (): void {
    $request = RequestFactory::make(['filter' => ['q' => 'John']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [
            new AllowedFilter('title', new PartialFilter),
            new AllowedFilter('author_id'), ])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT '.BOOK_FIELDS.' FROM books WHERE (books.title LIKE ? OR books.author_id = ?)',
    );
    expect($query->bindings)->toBe(['%John%', 'John']);
});

it('joins the members with and when asked to', function (): void {
    $request = RequestFactory::make(['filter' => ['match' => 'John']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilterGroup('match', [
            new AllowedFilter('title', new PartialFilter),
            new AllowedFilter('author_id'), ], Conjunction::AND)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT '.BOOK_FIELDS.' FROM books WHERE (books.title LIKE ? AND books.author_id = ?)',
    );
    expect($query->bindings)->toBe(['%John%', 'John']);
});

it('joins a group to a plain filter with and', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => '1', 'q' => 'John']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        #[AllowedFilterGroup('q', [
            new AllowedFilter('title', new PartialFilter),
            new AllowedFilter('author_id'), ])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(implode(' ', [
        'SELECT '.BOOK_FIELDS.' FROM books',
        'WHERE books.id = ?',
        'AND (books.title LIKE ? OR books.author_id = ?)',
    ]));
    expect($query->bindings)->toBe(['1', '%John%', 'John']);
});

it('joins several groups to each other with and', function (): void {
    $request = RequestFactory::make(['filter' => ['q' => 'John', 'loc' => 'ist']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [
            new AllowedFilter('title', new PartialFilter),
            new AllowedFilter('author_id'),
        ])]
        #[AllowedFilterGroup('loc', [
            new AllowedFilter('title', new PartialFilter), ])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(implode(' ', [
        'SELECT '.BOOK_FIELDS.' FROM books',
        'WHERE (books.title LIKE ? OR books.author_id = ?)',
        'AND books.title LIKE ?',
    ]));
    expect($query->bindings)->toBe(['%John%', 'John', '%ist%']);
});

it('lets a member filter on a column of its own naming', function (): void {
    $request = RequestFactory::make(['filter' => ['q' => '1']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [
            new AllowedFilter('author', alias: 'author_id'), ])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.author_id = ?');
    expect($query->bindings)->toBe(['1']);
});

it('drops a member whose values it refuses to act on', function (): void {
    $request = RequestFactory::make(['filter' => ['q' => 'John']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [
            new AllowedFilter('title', new PartialFilter),
            new AllowedFilter('author_id', ignore: ['John']), ])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title LIKE ?');
    expect($query->bindings)->toBe(['%John%']);
});

it('leaves the query alone when every member refused the value', function (): void {
    $request = RequestFactory::make(['filter' => ['q' => 'John']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [
            new AllowedFilter('title', ignore: ['John']),
            new AllowedFilter('author_id', ignore: ['John']), ])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books');
});

it('rejects a filter that is neither allowed nor a group', function (): void {
    $request = RequestFactory::make(['filter' => ['secret' => '1']]);

    new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [new AllowedFilter('title')])]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidFilterQuery::class, 'Filter `secret` is not allowed. Allowed filters: `q`.');

it('refuses a group without members', function (): void {
    new AllowedFilterGroup('q', []);
})->throws(FilterGroupWasInvalid::class, 'Filter group `q` has no members.');

it('refuses a group whose member is not a filter', function (): void {
    // @phpstan-ignore argument.type
    new AllowedFilterGroup('q', ['title']);
})->throws(FilterGroupWasInvalid::class);

it('refuses a scope as a group member', function (): void {
    $request = RequestFactory::make(['filter' => ['q' => 'temp']]);

    new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [
            new AllowedFilter('title', new ScopeFilter(TitleStartsWith::class)), ])]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(ScopeWasInvalid::class);
