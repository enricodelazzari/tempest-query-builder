<?php

namespace EnricoDeLazzari\QueryBuilder\Tests\Support\Factories;

use Tempest\Http\GenericRequest;
use Tempest\Http\Method;

class RequestFactory
{
    public static function make(array $query): GenericRequest
    {
        $queryString = http_build_query($query);

        $method = Method::GET;
        $uri = "https://domain.test/books?{$queryString}";

        return new GenericRequest($method, $uri);
    }
}
