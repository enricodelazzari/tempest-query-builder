<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Migrations;

use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Author;
use Tempest\Database\MigratesUp;
use Tempest\Database\QueryStatement;
use Tempest\Database\QueryStatements\CreateTableStatement;

final class CreateAuthorsTable implements MigratesUp
{
    public private(set) string $name = '0000-00-01_create_authors_table';

    public function up(): QueryStatement
    {
        return CreateTableStatement::forModel(Author::class)
            ->primary()
            ->text('name');
    }
}
