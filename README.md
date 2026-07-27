# Tempest query builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/enricodelazzari/tempest-query-builder.svg?style=flat-square)](https://packagist.org/packages/enricodelazzari/tempest-query-builder)
[![Tests](https://img.shields.io/github/actions/workflow/status/enricodelazzari/tempest-query-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/enricodelazzari/tempest-query-builder/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/enricodelazzari/tempest-query-builder.svg?style=flat-square)](https://packagist.org/packages/enricodelazzari/tempest-query-builder)

Filter, sort, eager load and narrow [Tempest](https://tempestphp.com) models straight from the HTTP request, in the
spirit of [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder).

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
| `ScopeFilter`      | Hands over to one of Tempest's `QueryScope` implementations                       |

`AllowedFilter` also accepts:

```php
#[AllowedFilter(
    name: 'author',          // name exposed in the query string
    filter: new ExactFilter,
    alias: 'author_id',      // column to filter on, when it differs from the name
    delimiter: '|',          // how to split several values, defaults to a comma
    default: '1',            // applied when the request has no such filter
    ignore: ['all'],         // values the filter refuses to act on
    nullable: true,          // let an empty value match `NULL`
)]
```

Empty values are ignored, so `?filter[title]=` leaves the query untouched — unless the filter is `nullable`, in which
case an empty value selects the rows whose column is null. That is applied as `IS NULL` whatever the filter strategy is,
since a `LIKE` against nothing has no useful meaning.

Values listed in `ignore` are dropped from what the request asked for, so `?filter[title]=all,tempest` with
`ignore: ['all']` filters on `tempest` alone. When every value asked for is ignored, the filter is not applied at all.

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

### Filter groups

A group fans one query parameter out over several filters, so `?filter[q]=John` can search more than one column at once:

```php
#[Model(Book::class)]
#[AllowedFilterGroup('q', [
    new AllowedFilter('title', new PartialFilter),
    new AllowedFilter('subtitle', new PartialFilter),
])]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
```

```http
GET /books?filter[q]=John
```

```sql
WHERE (books.title LIKE ? OR books.subtitle LIKE ?)
```

Members are joined with `OR` by default; pass `Conjunction::AND` for the other one. Groups are joined to each other, and
to plain filters, with `AND`:

```php
#[AllowedFilter('author_id')]
#[AllowedFilterGroup('q', [...])]
#[AllowedFilterGroup('loc', [...], Conjunction::AND)]
```

```sql
WHERE books.author_id = ? AND (…) AND (…)
```

A member contributes its column and its filter, and splits the group's value with its own `delimiter` and `ignore` — a
member that refuses every value it is given drops out of the group, and a group whose members all drop out leaves the
query alone. A member's `default` and `nullable` are not used: they describe whether a parameter was asked for, which is
the group's business rather than the member's.

Two things are checked while the attribute is read, rather than when a request eventually reaches it: a group needs at
least one member, and every member has to be an `AllowedFilter`. A `ScopeFilter` cannot be a member, because Tempest
applies a scope to a whole query rather than to a group of conditions — allow it as a filter of its own instead.

### Filtering through a query scope

`ScopeFilter` hands the request over to one of Tempest's query scopes. A scope carries its values on its constructor
rather than receiving them when it runs, so that is where the request value goes:

```php
use Tempest\Database\Builder\QueryBuilders\QueryScope;
use Tempest\Database\Builder\QueryBuilders\SupportsWhereStatements;
use Tempest\Database\Builder\WhereOperator;

final readonly class PublishedBetween implements QueryScope
{
    public function __construct(
        private string $from,
        private string $to,
    ) {}

    public function apply(SupportsWhereStatements $builder): void
    {
        $builder->whereField('published_at', [$this->from, $this->to], WhereOperator::BETWEEN);
    }
}
```

```php
#[Model(Book::class)]
#[AllowedFilter('published_between', new ScopeFilter(PublishedBetween::class))]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
```

```http
GET /books?filter[published_between]=2024-01-01,2024-12-31
```

Several values are spread over the constructor, so the scope above receives both dates. The scope decides which column
it touches, which means the name the filter is exposed under — and any `alias` on it — has no bearing here.

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

## Sparse fieldsets

`?fields[books]=title` narrows the columns the query selects, keyed by the model's table name:

```php
#[Model(Book::class)]
#[AllowedField('id')]
#[AllowedField('title')]
final class BookQueryBuilder
{
    use HasQueryBuilder;
}
```

```http
GET /books?fields[books]=title,id
```

```sql
SELECT books.title AS `books.title`, books.id AS `books.id` FROM `books`
```

Columns are selected in the order the request asked for them. A field is named after the model's column and cannot be
exposed under a different name, because the column is what the result is keyed by when the model is hydrated.

Two things worth knowing:

- **Only the model's own table can be narrowed.** A fieldset for another table, including one loaded through
  `?include=`, is ignored: Tempest builds the columns of an eager loaded relation itself, and the package has no say in
  it. So `?fields[books]=title&include=author` returns the author in full.
- **The primary key is not selected unless you ask for it.** That is what a sparse fieldset means, but it has a
  consequence: reading `$book->id` on a model whose key was not selected throws Tempest's `RelationWasMissing`.
  Serializing the model is fine — the key is simply absent from the output.

## Rejecting unknown parameters

By default, asking for a filter, sort, include or field that was not allowed throws — `InvalidFilterQuery`,
`InvalidSortQuery`, `InvalidIncludeQuery` or `InvalidFieldQuery`. They convert to a `400 Bad Request` JSON response
listing what was requested and what is allowed, so clients get a usable error instead of silently different results.

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
    fieldsParameter: 'fields',
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
composer test            # Pest
composer analyse         # PHPStan, at max level
composer refactor-check  # Rector, reporting without writing
composer refactor        # Rector, applying its changes
composer format          # Pint
```

Each of these runs in CI on every pull request.

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
