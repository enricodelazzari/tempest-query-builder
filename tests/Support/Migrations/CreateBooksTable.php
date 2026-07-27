<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Migrations;

use EnricoDeLazzari\QueryBuilder\Tests\Support\Models\Book;
use Tempest\Database\MigratesUp;
use Tempest\Database\QueryStatement;
use Tempest\Database\QueryStatements\CreateTableStatement;

final class CreateBooksTable implements MigratesUp
{
    public private(set) string $name = '0000-00-02_create_books_table';

    public function up(): QueryStatement
    {
        return CreateTableStatement::forModel(Book::class)
            ->primary()
            ->text('title')
            ->belongsTo('books.author_id', 'authors.id', nullable: true);
    }
}
