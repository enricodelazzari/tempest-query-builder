<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Models;

use Tempest\Database\IsDatabaseModel;

final class Book
{
    use IsDatabaseModel;

    public string $title;

    public ?Author $author = null;
}
