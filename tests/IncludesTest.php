<?php

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Includes\RelationshipInclude;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;

it('can build query without includes in request', function () {

    $request = RequestFactory::make([]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedInclude(include: new RelationshipInclude, name: 'author')]
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
