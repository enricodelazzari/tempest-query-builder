<?php

use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;

use function Tempest\Database\query;

it('can test', function () {

    $request = RequestFactory::make([]);

    $query = new
        #[Model(name: Book::class)]
        class($request)
        {
            use HasQueryBuilder;
        };

    // dd(query(Book::class)->select()->toSql());

    expect($query->toSql())->toBe(implode("\n", [
        'SELECT books.id AS `books.id`',
        'FROM `books`',
    ]));

    expect($query->getBindings())->toBe([]);
});
