<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedField;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\AggregateWasInvalid;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Includes\AggregateInclude;
use EnricoDeLazzari\QueryBuilder\Includes\CountInclude;
use EnricoDeLazzari\QueryBuilder\Includes\ExistsInclude;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Migrations\CreateAuthorsTable;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Migrations\CreateBooksTable;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Author;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use Tempest\Database\AggregateFunction;
use Tempest\Database\Migrations\CreateMigrationsTable;

beforeEach(function (): void {
    $this->database->reset(migrate: false);
    $this->database->migrate(
        CreateMigrationsTable::class,
        CreateAuthorsTable::class,
        CreateBooksTable::class,
    );

    $tolkien = Author::new(name: 'Tolkien')->save();
    Author::new(name: 'Nobody')->save();

    Book::new(title: 'The Hobbit', author: $tolkien)->save();
    Book::new(title: 'The Silmarillion', author: $tolkien)->save();
});

/**
 * Reads an aggregate off every author, keyed by name and ordered by it: none of
 * these queries sorts, and the three databases do not agree on the order rows
 * come back in when nothing asks them to.
 *
 * @return array<string, int|null>
 */
function aggregated(array $authors, string $property): array
{
    $values = [];

    foreach ($authors as $author) {
        $values[$author->name] = $author->{$property};
    }

    ksort($values);

    return $values;
}

it('counts the records of a relation', function (): void {
    $request = RequestFactory::make(['include' => 'booksCount']);

    $query = new
        #[Model(Author::class)]
        #[AllowedInclude('booksCount', new CountInclude, alias: 'books')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(aggregated($query->all(), 'booksCount'))->toBe([
        'Nobody' => 0,
        'Tolkien' => 2,
    ]);
});

it('reports whether a relation has any record', function (): void {
    $request = RequestFactory::make(['include' => 'booksExists']);

    $query = new
        #[Model(Author::class)]
        #[AllowedInclude('booksExists', new ExistsInclude, alias: 'books')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(aggregated($query->all(), 'booksExists'))->toBe([
        'Nobody' => 0,
        'Tolkien' => 1,
    ]);
});

it('aggregates a column of a relation', function (): void {
    $request = RequestFactory::make(['include' => 'booksMax']);

    $query = new
        #[Model(Author::class)]
        #[AllowedInclude('booksMax', new AggregateInclude(AggregateFunction::MAX, 'id'), alias: 'books')]
        class($request)
        {
            use HasQueryBuilder;
        };

    // A relation with no records aggregates to null, the answer SQL gives for
    // an aggregate over no rows.
    expect(aggregated($query->all(), 'booksMax'))->toBe([
        'Nobody' => null,
        'Tolkien' => 2,
    ]);
});

it('selects the aggregate alongside the model columns', function (): void {
    $request = RequestFactory::make(['include' => 'booksCount']);

    $query = new
        #[Model(Author::class)]
        #[AllowedInclude('booksCount', new CountInclude, alias: 'books')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toContain(
        'SELECT authors.id AS authors.id, authors.name AS authors.name, booksCount_aggregate.aggregate AS booksCount',
    );
});

it('keeps a sparse fieldset when an aggregate is requested too', function (): void {
    $request = RequestFactory::make([
        'include' => 'booksCount',
        'fields' => ['authors' => 'name'],
    ]);

    $query = new
        #[Model(Author::class)]
        #[AllowedField('name')]
        #[AllowedInclude('booksCount', new CountInclude, alias: 'books')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toContain(
        'SELECT authors.name AS authors.name, booksCount_aggregate.aggregate AS booksCount',
    );
});

it('leaves the query untouched when no aggregate is requested', function (): void {
    $query = new
        #[Model(Author::class)]
        #[AllowedInclude('booksCount', new CountInclude, alias: 'books')]
        class(RequestFactory::make())
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.AUTHOR_COLUMNS.' FROM authors');
});

it('counts a relation reached through an alias of its own', function (): void {
    $request = RequestFactory::make(['include' => 'writtenCount']);

    $query = new
        #[Model(Author::class)]
        #[AllowedInclude('writtenCount', new CountInclude, alias: 'books')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toContain('writtenCount_aggregate');
});

it('selects an aggregate and eager loads a relation at once', function (): void {
    $request = RequestFactory::make(['include' => 'booksCount,books']);

    $query = new
        #[Model(Author::class)]
        #[AllowedInclude('booksCount', new CountInclude, alias: 'books')]
        #[AllowedInclude('books')]
        class($request)
        {
            use HasQueryBuilder;
        };

    $authors = [];

    foreach ($query->all() as $author) {
        $authors[$author->name] = [count($author->books), $author->booksCount];
    }

    ksort($authors);

    expect($authors)->toBe([
        'Nobody' => [0, 0],
        'Tolkien' => [2, 2],
    ]);
});

it('refuses an aggregate pointed at something that is not a relation', function (): void {
    $request = RequestFactory::make(['include' => 'nonsenseCount']);

    new
        #[Model(Author::class)]
        #[AllowedInclude('nonsenseCount', new CountInclude, alias: 'nonsense')]
        class($request)
        {
            use HasQueryBuilder;
        };
})->throws(AggregateWasInvalid::class, 'does not define `nonsense` as one');
