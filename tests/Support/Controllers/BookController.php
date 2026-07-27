<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Controllers;

use EnricoDeLazzari\QueryBuilder\Tests\Support\Queries\BookQueryBuilder;
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
