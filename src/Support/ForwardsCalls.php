<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Support;

use BadMethodCallException;
use Error;

trait ForwardsCalls
{
    /**
     * Calls a method on the given object, re-throwing "undefined method" errors
     * as if they came from this class.
     *
     * @throws BadMethodCallException
     */
    protected function forwardCallTo(mixed $object, string $method, array $parameters): mixed
    {
        try {
            return $object->{$method}(...$parameters);
        } catch (Error|BadMethodCallException $exception) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^(]+)\(\)$~';

            if (! preg_match($pattern, $exception->getMessage(), $matches)) {
                throw $exception;
            }

            if ($matches['class'] !== $object::class || $matches['method'] !== $method) {
                throw $exception;
            }

            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()',
                static::class,
                $method,
            ));
        }
    }

    /**
     * Forwards a method call to the given object, returning `$this` when the
     * forwarded call returned the object itself, so chaining stays on this class.
     *
     * @throws BadMethodCallException
     */
    protected function forwardDecoratedCallTo(mixed $object, string $method, array $parameters): mixed
    {
        $result = $this->forwardCallTo($object, $method, $parameters);

        return $result === $object ? $this : $result;
    }
}
