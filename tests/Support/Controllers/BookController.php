<?php

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Controllers;

use EnricoDeLazzari\QueryBuilder\Tests\Support\Queries\BookQueryBuilder;
use Tempest\Http\ContentType;
use Tempest\Http\Request;
use Tempest\Http\Response;
use Tempest\Http\Responses\Ok;
use Tempest\Router\Get;

final readonly class BookController
{
    #[Get('/books')]
    public function __invoke(
        Request $request,
        BookQueryBuilder $query
    ): Response {
        return new Ok([
            // 'data' => $query->all(),
        ])->setContentType(ContentType::JSON);
    }
}
