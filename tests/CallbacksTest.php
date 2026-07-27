<?php

declare(strict_types=1);

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilterGroup;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Filters\CallbackFilter;
use EnricoDeLazzari\QueryBuilder\Filters\PartialFilter;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use EnricoDeLazzari\QueryBuilder\Includes\CallbackInclude;
use EnricoDeLazzari\QueryBuilder\Sorts\CallbackSort;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Factories\RequestFactory;
use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Builder\QueryBuilders\WhereGroupBuilder;
use Tempest\Database\Builder\WhereOperator;
use Tempest\Database\Direction;

it('filters with a closure', function (): void {
    $request = RequestFactory::make(['filter' => ['pages' => '100']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('pages', new CallbackFilter(static function (SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void {
            $query->whereField('id', $value, WhereOperator::GREATER_THAN);
        }))]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`id` > ?');
    expect($query->bindings)->toBe(['100']);
});

it('gives the closure the column the attribute resolved', function (): void {
    $request = RequestFactory::make(['filter' => ['author' => '1']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('author', new CallbackFilter(static function (SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void {
            $query->whereField($column, $value);
        }), alias: 'author_id')]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`author_id` = ?');
    expect($query->bindings)->toBe(['1']);
});

it('filters with a closure inside a group', function (): void {
    $request = RequestFactory::make(['filter' => ['q' => 'John']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilterGroup('q', [
            new AllowedFilter('title', new PartialFilter),
            new AllowedFilter('author_id', new CallbackFilter(static function (SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void {
                $query->whereField($column, $value);
            })), ])]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT '.BOOK_FIELDS.' FROM `books` WHERE (`books`.`title` LIKE ? OR `books`.`author_id` = ?)',
    );
    expect($query->bindings)->toBe(['%John%', 'John']);
});

it('sorts with a closure', function (): void {
    $request = RequestFactory::make(['sort' => '-title']);

    $query = new
        #[Model(Book::class)]
        #[AllowedSort('title', new CallbackSort(static function (SelectQueryBuilder $query, string $column, Direction $direction): void {
            $query->orderByRaw("LENGTH({$column}) {$direction->value}");
        }))]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` ORDER BY LENGTH(title) DESC');
});

it('includes with a closure', function (): void {
    $request = RequestFactory::make(['include' => 'author']);

    $query = new
        #[Model(Book::class)]
        #[AllowedInclude('author', new CallbackInclude(static function (SelectQueryBuilder $query, string $relation): void {
            $query->with($relation);
        }))]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe(
        'SELECT '.BOOK_FIELDS.', '.AUTHOR_FIELDS.' FROM `books` '.AUTHOR_JOIN,
    );
});

it('accepts a first class callable', function (): void {
    $request = RequestFactory::make(['filter' => ['title' => 'tempest']]);

    $query = new
        #[Model(Book::class)]
        #[AllowedFilter('title', new CallbackFilter(Callbacks::exact(...)))]
        class($request)
        {
            use HasQueryBuilder;
        };

    expect(sql($query))->toBe('SELECT '.BOOK_FIELDS.' FROM `books` WHERE `books`.`title` = ?');
    expect($query->bindings)->toBe(['tempest']);
});

final class Callbacks
{
    /**
     * @param  string|string[]  $value
     */
    public static function exact(SelectQueryBuilder|WhereGroupBuilder $query, string $column, string|array $value): void
    {
        $query->whereField($column, $value);
    }
}
