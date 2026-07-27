<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Models;

use Tempest\Database\IsDatabaseModel;

final class Author
{
    use IsDatabaseModel;

    public function __construct(
        public string $name = '',

        /** @var \EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book[] */
        public array $books = [],
    ) {}
}
