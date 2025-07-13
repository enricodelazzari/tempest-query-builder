<?php

use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;

it('can not build query without model', function () {

    $request = RequestFactory::make([]);

    new class($request)
    {
        use HasQueryBuilder;
    };
})->throws(\Exception::class);

it('can not build query without a valid model', function () {
    $request = RequestFactory::make([]);

    $query = new
        #[Model(name: 'invalid-model-string')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(\Exception::class);

it('can build query with a valid model', function () {

    $request = RequestFactory::make([]);

    $query = new
        #[Model(name: Book::class)]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode("\n", [
        'SELECT books.id AS `books.id`',
        'FROM `books`',
    ]));

    expect($query->getBindings())->toBe([]);
});
