<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\DefaultSort;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidSortQuery;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use Tempest\Database\Direction;

it('leaves the query unordered when nothing is requested', function (): void {
    $query = new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books`');
});

it('sorts ascending by default', function (): void {
    $request = RequestFactory::make(['sort' => 'title']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`title` ASC');
});

it('sorts descending when the name is prefixed with a hyphen', function (): void {
    $request = RequestFactory::make(['sort' => '-title']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`title` DESC');
});

it('sorts by several columns in the requested order', function (): void {
    $request = RequestFactory::make(['sort' => '-title,id']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        #[AllowedSort('id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`title` DESC, `books`.`id` ASC');
});

it('sorts on a column that differs from the exposed name', function (): void {
    $request = RequestFactory::make(['sort' => 'author']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('author', alias: 'author_id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`author_id` ASC');
});

it('applies the default sort when nothing is requested', function (): void {
    $query = new
        #[Model(Book::class)]
        #[DefaultSort('title', Direction::DESC)]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`title` DESC');
});

it('applies several default sorts', function (): void {
    $query = new
        #[Model(Book::class)]
        #[DefaultSort('title')]
        #[DefaultSort('id', Direction::DESC)]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`title` ASC, `books`.`id` DESC');
});

it('lets the request override the default sort', function (): void {
    $request = RequestFactory::make(['sort' => 'id']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('id')]
        #[DefaultSort('title', Direction::DESC)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`id` ASC');
});

it('falls back to the default sort when no requested sort could be applied', function (): void {
    $request = RequestFactory::make(['sort' => 'unknown']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('id')]
        #[DefaultSort('title', Direction::DESC)]
        class($request, new QueryBuilderConfig(strict: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`title` DESC');
});

it('rejects a sort that was not allowed', function (): void {
    $request = RequestFactory::make(['sort' => '-secret']);

    new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidSortQuery::class, 'Sort `-secret` is not allowed. Allowed sorts: `title`.');

it('ignores a sort that was not allowed when strict mode is off', function (): void {
    $request = RequestFactory::make(['sort' => 'secret,title']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        class($request, new QueryBuilderConfig(strict: false))
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY `books`.`title` ASC');
});

it('rejects a sort whose direction prefix is malformed', function (): void {
    $request = RequestFactory::make(['sort' => '--title']);

    new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidSortQuery::class, 'Sort `--title` is not allowed. Allowed sorts: `title`.');

it('rejects a sort that is nothing but a direction prefix', function (): void {
    $request = RequestFactory::make(['sort' => '-']);

    new
        #[Model(Book::class)]
        #[AllowedSort('title')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(InvalidSortQuery::class, 'Sort `-` is not allowed. Allowed sorts: `title`.');
