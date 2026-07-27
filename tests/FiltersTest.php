<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFilterQuery;
use EnricoDeLazzari\QueryBuilder\Filters\BeginsWithFilter;
use EnricoDeLazzari\QueryBuilder\Filters\EndsWithFilter;
use EnricoDeLazzari\QueryBuilder\Filters\ExactFilter;
use EnricoDeLazzari\QueryBuilder\Filters\OperatorFilter;
use EnricoDeLazzari\QueryBuilder\Filters\PartialFilter;
use EnricoDeLazzari\QueryBuilder\Filters\RelationFilter;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use Tempest\Database\Builder\WhereOperator;

it('leaves the query untouched when the request has no filters', function () {
    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books`');
    expect($query->bindings)->toBe([]);
});

it('applies an exact filter', function () {
    $request = RequestFactory::make(['filter' => ['id' => '1']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`id` = ?');
    expect($query->bindings)->toBe(['1']);
});

it('applies an exact filter with comma separated values', function () {
    $request = RequestFactory::make(['filter' => ['id' => '1,2']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id', new ExactFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`id` IN (?,?)');
    expect($query->bindings)->toBe(['1', '2']);
});

it('applies an exact filter with array syntax', function () {
    $request = RequestFactory::make(['filter' => ['id' => ['1', '2']]]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`id` IN (?,?)');
    expect($query->bindings)->toBe(['1', '2']);
});

it('applies several filters at once', function () {
    $request = RequestFactory::make(['filter' => ['id' => '1', 'title' => 'tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id')]
        #[AllowedFilter('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`id` = ? AND `books`.`title` = ?');
    expect($query->bindings)->toBe(['1', 'tempest']);
});

it('applies a partial filter', function () {
    $request = RequestFactory::make(['filter' => ['title' => 'tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', new PartialFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`title` LIKE ?');
    expect($query->bindings)->toBe(['%tempest%']);
});

it('combines several values of a partial filter with or', function () {
    $request = RequestFactory::make(['filter' => ['title' => 'tempest,laravel']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', new PartialFilter)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE (`books`.`title` LIKE ? OR `books`.`title` LIKE ?)');
    expect($query->bindings)->toBe(['%tempest%', '%laravel%']);
});

it('applies a begins with filter', function () {
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

it('applies an ends with filter', function () {
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

it('applies an operator filter', function () {
    $request = RequestFactory::make(['filter' => ['id' => '10']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id', new OperatorFilter(WhereOperator::GREATER_THAN))]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`id` > ?');
    expect($query->bindings)->toBe(['10']);
});

it('filters on a relation', function () {
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

it('filters on a column that differs from the exposed name', function () {
    $request = RequestFactory::make(['filter' => ['author' => '1']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('author', alias: 'author_id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`author_id` = ?');
    expect($query->bindings)->toBe(['1']);
});

it('splits values on a filter specific delimiter', function () {
    $request = RequestFactory::make(['filter' => ['id' => '1|2']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('id', delimiter: '|')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`id` IN (?,?)');
    expect($query->bindings)->toBe(['1', '2']);
});

it('falls back to the default value of a filter', function () {
    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', default: 'tempest')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`title` = ?');
    expect($query->bindings)->toBe(['tempest']);
});

it('prefers the request value over the default value of a filter', function () {
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

it('ignores empty filter values', function () {
    $request = RequestFactory::make(['filter' => ['title' => '']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books`');
    expect($query->bindings)->toBe([]);
});

it('rejects a filter that was not allowed', function () {
    $request = RequestFactory::make(['filter' => ['secret' => '1']]);

    new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidFilterQuery::class, 'Requested filters `secret` is not allowed. Allowed filters: `title`.');

it('ignores a filter that was not allowed when strict mode is off', function () {
    $request = RequestFactory::make(['filter' => ['secret' => '1', 'title' => 'tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title')]
        class($request, new QueryBuilderConfig(strict: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`title` = ?');
    expect($query->bindings)->toBe(['tempest']);
});
