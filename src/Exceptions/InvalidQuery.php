<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Exceptions;

use Exception;
use Tempest\Http\Response;
use Tempest\Http\Responses\Json;
use Tempest\Http\Status;
use Tempest\Router\Exceptions\ConvertsToResponse;

/**
 * Thrown when the request asks for something the query builder did not allow.
 * Rendered as a `400 Bad Request` when it bubbles up through the router.
 */
abstract class InvalidQuery extends Exception implements ConvertsToResponse, QueryBuilderException
{
    /**
     * Singular name of what was rejected, e.g. `filter`.
     */
    protected const string SUBJECT = 'parameter';

    /**
     * @param  string[]  $requested  names that were rejected
     * @param  string[]  $allowed  names that would have been accepted
     */
    final public function __construct(
        public readonly array $requested,
        public readonly array $allowed,
    ) {
        $single = count($requested) === 1;

        parent::__construct(sprintf(
            '%s %s %s not allowed. Allowed %ss: %s.',
            ucfirst($single ? static::SUBJECT : static::SUBJECT.'s'),
            self::quote($requested),
            $single ? 'is' : 'are',
            static::SUBJECT,
            $allowed === [] ? 'none' : self::quote($allowed),
        ));
    }

    /**
     * @param  string[]  $requested
     * @param  string[]  $allowed
     */
    public static function forNames(array $requested, array $allowed): static
    {
        return new static(array_values($requested), array_values($allowed));
    }

    public function convertToResponse(): Response
    {
        return new Json(
            body: [
                'message' => $this->getMessage(),
                'requested' => $this->requested,
                'allowed' => $this->allowed,
            ],
            status: Status::BAD_REQUEST,
        );
    }

    /**
     * @param  string[]  $names
     */
    private static function quote(array $names): string
    {
        return '`'.implode('`, `', $names).'`';
    }
}
