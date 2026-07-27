<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\ModelWasInvalid;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

it('cannot be built without a model attribute', function (): void {
    new class(RequestFactory::make())
    {
        use HasQueryBuilder;
    };
})->throws(ModelWasInvalid::class);

it('cannot be built with something that is not a database model', function (): void {
    new
        #[Model('not-a-model')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };
})->throws(ModelWasInvalid::class);

it('builds a select query for the model', function (): void {
    $query = new
        #[Model(Book::class)]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books`');
    expect($query->bindings)->toBe([]);
});

it('exposes the underlying tempest query builder', function (): void {
    $query = new
        #[Model(Book::class)]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect($query->query)->toBeInstanceOf(SelectQueryBuilder::class);
});

it('forwards calls to the underlying query builder', function (): void {
    $query = new
        #[Model(Book::class)]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    $result = $query->whereField('id', 1);

    expect($result)->toBe($query);
    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`id` = ?');
    expect($query->bindings)->toBe([1]);
});

it('fails on methods the query builder does not have', function (): void {
    $query = new
        #[Model(Book::class)]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    $query->thisMethodDoesNotExist();
})->throws(BadMethodCallException::class);
