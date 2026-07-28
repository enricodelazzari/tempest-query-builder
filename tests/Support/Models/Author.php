<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Models;

use Tempest\Database\IsDatabaseModel;
use Tempest\Database\Virtual;

final class Author
{
    use IsDatabaseModel;

    /**
     * Aggregates land on the model like any other selected column, so they need
     * a property to land on. `#[Virtual]` is what keeps them out of the columns
     * the model selects by default, since no table holds them.
     */
    #[Virtual]
    public int $booksCount = 0;

    #[Virtual]
    public int $booksExists = 0;

    #[Virtual]
    public ?int $booksMax = null;

    public function __construct(
        public string $name = '',

        /** @var \EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book[] */
        public array $books = [],
    ) {}
}
