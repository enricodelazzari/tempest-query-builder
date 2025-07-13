# Tempest query builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/enricodelazzari/tempest-query-builder.svg?style=flat-square)](https://packagist.org/packages/enricodelazzari/tempest-query-builder)
[![Tests](https://img.shields.io/github/actions/workflow/status/enricodelazzari/tempest-query-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/enricodelazzari/tempest-query-builder/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/enricodelazzari/tempest-query-builder.svg?style=flat-square)](https://packagist.org/packages/enricodelazzari/tempest-query-builder)

This package allows you to filter, sort and include relations based on a http request. The QueryBuilder used in this package extends Tempest's default query builder. This means all your favorite methods are still available.

## Installation

You can install the package via composer:

```bash
composer require enricodelazzari/tempest-query-builder
```

## Usage

```php
$skeleton = new EnricoDeLazzari\QueryBuilder();
echo $skeleton->echoPhrase('Hello, EnricoDeLazzari!');
```

## Features

- [`filtering`](#filtering)
- [`sorting`](#sorting)
- [`including`](#including)

### `filtering`

The filter query parameters can be used to add where clauses to your query. Out of the box we support filtering results by partial attribute value and exact attribute value or even if an attribute value exists in a given array of values. For anything more advanced, custom filters can be used.

### `sorting`

The sort query parameter is used to determine by which property the results collection will be ordered. Sorting is ascending by default and can be reversed by adding a hyphen (-) to the start of the property name.

All sorts have to be explicitly allowed by passing an array to the allowedSorts() method. The allowedSorts method takes an array of column names as strings or instances of AllowedSorts.

### `including`

The include query parameter will load any relation on the resulting models. All includes must be explicitly allowed using allowedIncludes().

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
