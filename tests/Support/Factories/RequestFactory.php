<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Factories;

use Tempest\Http\GenericRequest;
use Tempest\Http\Method;

final class RequestFactory
{
    /**
     * @param  array<string, mixed>  $query
     */
    public static function make(array $query = []): GenericRequest
    {
        $uri = 'https://domain.test/books';

        if ($query !== []) {
            $uri .= '?'.http_build_query($query);
        }

        return new GenericRequest(Method::GET, $uri);
    }
}
