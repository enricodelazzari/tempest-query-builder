<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Tests\Support\Migrations\CreateAuthorsTable;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Migrations\CreateBooksTable;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Author;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use Tempest\Database\Migrations\CreateMigrationsTable;
use Tempest\Http\Status;

beforeEach(function () {
    $this->database->reset(migrate: false);
    $this->database->migrate(
        CreateMigrationsTable::class,
        CreateAuthorsTable::class,
        CreateBooksTable::class,
    );

    $tolkien = Author::new(name: 'Tolkien')->save();
    $herbert = Author::new(name: 'Herbert')->save();

    Book::new(title: 'The Hobbit', author: $tolkien)->save();
    Book::new(title: 'The Silmarillion', author: $tolkien)->save();
    Book::new(title: 'Dune', author: $herbert)->save();
});

/**
 * @return string[]
 */
function titles(array $books): array
{
    return array_map(static fn (Book $book): string => $book->title, $books);
}

it('returns every book ordered by the default sort', function () {
    $books = $this->http
        ->get('/books')
        ->assertOk()
        ->body['data'];

    expect(titles($books))->toBe(['Dune', 'The Silmarillion', 'The Hobbit']);
});

it('filters books through the query string', function () {
    $books = $this->http
        ->get('/books', ['filter' => ['title' => 'The']])
        ->assertOk()
        ->body['data'];

    expect(titles($books))->toBe(['The Silmarillion', 'The Hobbit']);
});

it('sorts books through the query string', function () {
    $books = $this->http
        ->get('/books', ['sort' => 'title'])
        ->assertOk()
        ->body['data'];

    expect(titles($books))->toBe(['Dune', 'The Hobbit', 'The Silmarillion']);
});

it('eager loads a relation through the query string', function () {
    $books = $this->http
        ->get('/books', ['include' => 'author', 'filter' => ['title' => 'Dune']])
        ->assertOk()
        ->body['data'];

    expect($books[0]->author->name)->toBe('Herbert');
});

it('answers with a bad request when the query asks for something not allowed', function () {
    $this->http
        ->get('/books', ['filter' => ['secret' => '1']])
        ->assertStatus(Status::BAD_REQUEST);
});

it('narrows the response through the query string', function () {
    $books = $this->http
        ->get('/books', [
            'fields' => ['books' => 'title'],
            'filter' => ['title' => 'Dune'],
        ])
        ->assertOk()
        ->body['data'];

    expect(json_encode($books))->toBe('[{"title":"Dune"}]');
});

it('answers with a bad request when the query asks for a field that was not allowed', function () {
    $this->http
        ->get('/books', ['fields' => ['books' => 'secret']])
        ->assertStatus(Status::BAD_REQUEST);
});
