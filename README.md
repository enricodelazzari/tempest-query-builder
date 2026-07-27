# Tempest query builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/enricodelazzari/tempest-query-builder.svg?style=flat-square)](https://packagist.org/packages/enricodelazzari/tempest-query-builder)
[![Tests](https://img.shields.io/github/actions/workflow/status/enricodelazzari/tempest-query-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/enricodelazzari/tempest-query-builder/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/enricodelazzari/tempest-query-builder.svg?style=flat-square)](https://packagist.org/packages/enricodelazzari/tempest-query-builder)

Filter, sort and eager load [Tempest](https://tempestphp.com) models straight from the HTTP request, in the spirit of
[spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder).

Everything a query accepts is declared with attributes, so a query builder is a small, readable class that doubles as the
documentation of your endpoint. What comes out is a plain Tempest `SelectQueryBuilder`, so every method you already know
stays available.

```php
use App\Models\Book;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedFilter;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedInclude;
use EnricoDeLazzari\QueryBuilder\Attributes\AllowedSort;
use EnricoDeLazzari\QueryBuilder\Attributes\DefaultSort;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Filters\PartialFilter;
use EnricoDeLazzari\QueryBuilder\HasQueryBuilder;
use Tempest\Database\Direction;

#[Model(Book::class)]
#[AllowedFilter('title', new PartialFilter)]
#[AllowedFilter('author_id')]
#[AllowedInclude('author')]
#[AllowedSort('title')]
#[AllowedSort('id')]
#[DefaultSort('id', Direction::DESC)]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
```

Type-hint it in a controller and the request is applied before you get it:

```php
use Tempest\Http\Response;
use Tempest\Http\Responses\Json;
use Tempest\Router\Get;

final readonly class BookController
{
    #[Get('/books')]
    public function __invoke(BookQueryBuilder $books): Response
    {
        return new Json(['data' => $books->all()]);
    }
}
```

```http
GET /books?filter[title]=tempest&sort=-title&include=author
```

## Requirements

- PHP 8.5+
- Tempest 3.17+

## Installation

```bash
composer require enricodelazzari/tempest-query-builder
```

## Filtering

`?filter[title]=tempest` adds a `WHERE` clause for every filter the query builder allows. Values separated by a comma
become a set:

```php
#[Model(Book::class)]
#[AllowedFilter('id')]                            // ?filter[id]=1        → books.id = ?
#[AllowedFilter('id')]                            // ?filter[id]=1,2      → books.id IN (?, ?)
#[AllowedFilter('title', new PartialFilter)]      // ?filter[title]=temp  → books.title LIKE '%temp%'
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
```

These filters ship with the package:

| Filter             | Result                                                                          |
|--------------------|---------------------------------------------------------------------------------|
| `ExactFilter`      | `column = ?`, or `column IN (?, …)` for several values. The default.             |
| `PartialFilter`    | `column LIKE '%value%'`. Several values are combined with `OR` inside a group.    |
| `BeginsWithFilter` | `column LIKE 'value%'`                                                           |
| `EndsWithFilter`   | `column LIKE '%value'`                                                           |
| `OperatorFilter`   | Any Tempest `WhereOperator`, e.g. `new OperatorFilter(WhereOperator::GREATER_THAN)` |
| `RelationFilter`   | `WHERE EXISTS` against a related model                                           |

`AllowedFilter` also accepts:

```php
#[AllowedFilter(
    name: 'author',        // name exposed in the query string
    filter: new ExactFilter,
    alias: 'author_id',    // column to filter on, when it differs from the name
    delimiter: '|',        // how to split several values, defaults to a comma
    default: '1',          // applied when the request has no such filter
)]
```

Empty values are ignored, so `?filter[title]=` leaves the query untouched.

### Filtering on a relation

`RelationFilter` moves the filter into a subquery on the related model. The column it filters on is the one the attribute
resolves, so use `alias` to name it:

```php
#[Model(Book::class)]
#[AllowedFilter('author', new RelationFilter('author'), alias: 'name')]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
```

```http
GET /books?filter[author]=tolkien
```

### Writing your own filter

```php
use EnricoDeLazzari\QueryBuilder\Filters\Filter;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

final class PublishedFilter implements Filter
{
    public function apply(SelectQueryBuilder $query, string $column, string|array $value): void
    {
        $query->whereNotNull($column);
    }
}
```

`$value` is a string when the request held a single value and a list when it held several, so a filter decides for itself
what several values mean. For `LIKE` filters, extend `LikeFilter` instead and only describe the pattern — combining
several values with `OR` is already handled:

```php
use EnricoDeLazzari\QueryBuilder\Filters\LikeFilter;

final class ContainsWordFilter extends LikeFilter
{
    protected function pattern(string $value): string
    {
        return "% {$value} %";
    }
}
```

## Sorting

`?sort=title` orders ascending, `?sort=-title` descending. Several sorts are applied in the order they were requested:

```php
#[Model(Book::class)]
#[AllowedSort('title')]
#[AllowedSort('published', alias: 'published_at')]
#[DefaultSort('id', Direction::DESC)]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
```

```http
GET /books?sort=-title,published
```

`DefaultSort` is repeatable and only applies when the request does not sort. Columns are qualified with the model's
table, so ordering keeps working next to an include that joins a table sharing a column name.

Custom sorts implement `EnricoDeLazzari\QueryBuilder\Sorts\Sort`.

## Including

`?include=author` eager loads a relation on the model. Nested relations use the dot notation Tempest already understands:

```php
#[Model(Book::class)]
#[AllowedInclude('author')]
#[AllowedInclude('author.publisher')]
#[AllowedInclude('writer', alias: 'author')]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
```

```http
GET /books?include=author,author.publisher
```

Custom includes implement `EnricoDeLazzari\QueryBuilder\Includes\Inclusion`.

## Rejecting unknown parameters

By default, asking for a filter, sort or include that was not allowed throws — `InvalidFilterQuery`, `InvalidSortQuery`
or `InvalidIncludeQuery`. They convert to a `400 Bad Request` JSON response listing what was requested and what is
allowed, so clients get a usable error instead of silently different results.

Turn this off to ignore unknown parameters instead:

```php
// query-builder.config.php
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;

return new QueryBuilderConfig(
    strict: false,
);
```

## Configuration

Publish a `query-builder.config.php` anywhere Tempest discovers configuration to rename the query parameters:

```php
use EnricoDeLazzari\QueryBuilder\QueryBuilderConfig;

return new QueryBuilderConfig(
    filterParameter: 'filter',
    sortParameter: 'sort',
    includeParameter: 'include',
    delimiter: ',',
    strict: true,
);
```

## Working with the query

A query builder forwards every unknown method to the underlying Tempest `SelectQueryBuilder`, so you can keep building on
top of what the request asked for:

```php
$books = $query
    ->whereField('published', true)
    ->limit(10)
    ->all();
```

`$query->query` gives you the `SelectQueryBuilder` itself, and `$query->bindings` the values bound so far.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Enrico De Lazzari](https://github.com/enricodelazzari)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
