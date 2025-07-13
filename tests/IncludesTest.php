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
        'SELECT books.author_id AS `books.author_id`, books.id AS `books.id`',
        'FROM `books`',
    ]));

    expect($query->getBindings())->toBe([]);
});

it('can build query with an include in request', function () {

    $request = RequestFactory::make([
        'includes' => 'author',
    ]);

    $query = new
        #[Model(name: Book::class)]
        #[AllowedInclude(include: new RelationshipInclude, name: 'author')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect($query->toSql())->toBe(implode(PHP_EOL, [
        'SELECT books.author_id AS `books.author_id`, books.id AS `books.id`, authors.id AS `author.id`',
        'FROM `books`',
        'LEFT JOIN authors ON authors.id = books.author_id',
    ]));

    expect($query->getBindings())->toBe([]);
});

// it('can build query with multiple includes in request', function () {

//     $request = RequestFactory::make([
//         'includes' => 'author,tags',
//     ]);

//     $query = new
//         #[Model(name: Book::class)]
//         #[AllowedInclude(include: new RelationshipInclude, name: 'author')]
//         #[AllowedInclude(include: new RelationshipInclude, name: 'tags')]
//         class($request)
//         {
//             use HasQueryBuilder;
//         };

//     expect($query->toSql())->dd()->toBe(implode(PHP_EOL, [
//         'SELECT books.author_id AS `books.author_id`, books.id AS `books.id`, authors.id AS `author.id`',
//         'FROM `books`',
//     ]));

//     expect($query->getBindings())->toBe([]);
// });
