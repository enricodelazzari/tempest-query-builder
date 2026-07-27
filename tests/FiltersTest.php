<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFilterQuery;
use EnricoDeLazzari\QueryBuilder\Exceptions\ScopeWasInvalid;
use EnricoDeLazzari\QueryBuilder\Filters\BeginsWithFilter;
use EnricoDeLazzari\QueryBuilder\Filters\EndsWithFilter;
use EnricoDeLazzari\QueryBuilder\Filters\ExactFilter;
use EnricoDeLazzari\QueryBuilder\Filters\OperatorFilter;
use EnricoDeLazzari\QueryBuilder\Filters\PartialFilter;
use EnricoDeLazzari\QueryBuilder\Filters\RelationFilter;
use EnricoDeLazzari\QueryBuilder\Filters\ScopeFilter;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Scopes\IdBetween;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Scopes\TitleStartsWith;
use Tempest\Database\Builder\WhereOperator;

it('leaves the query untouched when the request has no filters', function (): void {
    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books');
    expect($query->bindings)->toBe([]);
});

it('applies an exact filter', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => '1']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.id = ?');
    expect($query->bindings)->toBe(['1']);
});

it('applies an exact filter with comma separated values', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => '1,2']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id', new ExactFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.id IN (?,?)');
    expect($query->bindings)->toBe(['1', '2']);
});

it('applies an exact filter with array syntax', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => ['1', '2']]]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.id IN (?,?)');
    expect($query->bindings)->toBe(['1', '2']);
});

it('applies several filters at once', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => '1', 'title' => 'tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        #[AllowedFilter('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.id = ? AND books.title = ?');
    expect($query->bindings)->toBe(['1', 'tempest']);
});

it('applies a partial filter', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', new PartialFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title LIKE ?');
    expect($query->bindings)->toBe(['%tempest%']);
});

it('combines several values of a partial filter with or', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'tempest,laravel']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', new PartialFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE (books.title LIKE ? OR books.title LIKE ?)');
    expect($query->bindings)->toBe(['%tempest%', '%laravel%']);
});

it('applies a begins with filter', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'temp']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', new BeginsWithFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->bindings)->toBe(['temp%']);
});

it('applies an ends with filter', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'est']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', new EndsWithFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->bindings)->toBe(['%est']);
});

it('applies an operator filter', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => '10']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id', new OperatorFilter(WhereOperator::GREATER_THAN))]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.id > ?');
    expect($query->bindings)->toBe(['10']);
});

it('filters on a relation', function (): void {
    $request = RequestFactory::make(['filter' => ['author' => 'tolkien']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('author', new RelationFilter('author'), alias: 'name')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toContain('EXISTS');
    expect($query->bindings)->toBe(['tolkien']);
});

it('filters on a column that differs from the exposed name', function (): void {
    $request = RequestFactory::make(['filter' => ['author' => '1']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('author', alias: 'author_id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.author_id = ?');
    expect($query->bindings)->toBe(['1']);
});

it('splits values on a filter specific delimiter', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => '1|2']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id', delimiter: '|')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.id IN (?,?)');
    expect($query->bindings)->toBe(['1', '2']);
});

it('falls back to the default value of a filter', function (): void {
    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', default: 'tempest')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title = ?');
    expect($query->bindings)->toBe(['tempest']);
});

it('prefers the request value over the default value of a filter', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'laravel']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', default: 'tempest')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->bindings)->toBe(['laravel']);
});

it('ignores empty filter values', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => '']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books');
    expect($query->bindings)->toBe([]);
});

it('rejects a filter that was not allowed', function (): void {
    $request = RequestFactory::make(['filter' => ['secret' => '1']]);

    new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidFilterQuery::class, 'Filter `secret` is not allowed. Allowed filters: `title`.');

it('ignores a filter that was not allowed when strict mode is off', function (): void {
    $request = RequestFactory::make(['filter' => ['secret' => '1', 'title' => 'tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        class($request, new QueryBuilderConfig(strict: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title = ?');
    expect($query->bindings)->toBe(['tempest']);
});

it('lists every rejected filter when several were not allowed', function (): void {
    $request = RequestFactory::make(['filter' => ['secret' => '1', 'hidden' => '2']]);

    new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        #[AllowedFilter('id')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidFilterQuery::class, 'Filters `secret`, `hidden` are not allowed. Allowed filters: `title`, `id`.');

it('reports that nothing is allowed when the query builder allows no filters', function (): void {
    $request = RequestFactory::make(['filter' => ['secret' => '1']]);

    new
        #[Model(Book::class)]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidFilterQuery::class, 'Filter `secret` is not allowed. Allowed filters: none.');

it('drops a value the filter refuses to act on', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'forbidden,tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', ignore: ['forbidden'])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title = ?');
    expect($query->bindings)->toBe(['tempest']);
});

it('does not apply a filter whose values were all refused', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'forbidden,banned']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', ignore: ['forbidden', 'banned'])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books');
    expect($query->bindings)->toBe([]);
});

it('matches null when a nullable filter is asked for with no value', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => '']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', nullable: true)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title IS NULL');
    expect($query->bindings)->toBe([]);
});

it('leaves a nullable filter alone when the request does not ask for it', function (): void {
    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', nullable: true)]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books');
});

it('still filters normally when a nullable filter is given a value', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', nullable: true)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title = ?');
    expect($query->bindings)->toBe(['tempest']);
});

it('applies a query scope built from the request value', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'temp']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', new ScopeFilter(TitleStartsWith::class))]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.title LIKE ?');
    expect($query->bindings)->toBe(['temp%']);
});

it('spreads several values onto the scope constructor', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => '1,10']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id', new ScopeFilter(IdBetween::class))]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM books WHERE books.id BETWEEN ? AND ?');
    expect($query->bindings)->toBe(['1', '10']);
});

it('refuses a scope filter pointed at something that is not a query scope', function (): void {
    new ScopeFilter(Book::class);
})->throws(ScopeWasInvalid::class);

it('joins a multi-value partial filter to the filter before it with and', function (): void {
    $request = RequestFactory::make(['filter' => ['id' => '1', 'title' => 'a,b']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        #[AllowedFilter('title', new PartialFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(implode(' ', [
        'SELECT '.BOOK_FIELDS.' FROM books',
        'WHERE books.id = ?',
        'AND (books.title LIKE ? OR books.title LIKE ?)',
    ]));
    expect($query->bindings)->toBe(['1', '%a%', '%b%']);
});
