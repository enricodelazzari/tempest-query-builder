<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder;

use EnricoDeLazzari\QueryBuilder\Appliers\QueryApplier;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Exceptions\ModelWasInvalid;
use EnricoDeLazzari\QueryBuilder\Support\ForwardsCalls;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;

use function Tempest\Database\inspect;
use function Tempest\Database\query;

/**
 * Turns a class annotated with `#[Model]`, `#[AllowedFilter]`, `#[AllowedSort]`,
 * `#[DefaultSort]` and `#[AllowedInclude]` into a Tempest select query that is
 * already filtered, sorted and eager loaded according to the request.
 *
 * Any method that is not defined on the class itself is forwarded to the
 * underlying `SelectQueryBuilder`.
 *
 * @mixin SelectQueryBuilder
 */
trait HasQueryBuilder
{
    use ForwardsCalls;

    /**
     * The underlying Tempest query, with the request already applied.
     */
    public private(set) SelectQueryBuilder $query;

    /**
     * Values bound to the query so far.
     */
    public array $bindings {
        get => $this->query->bindings;
    }

    public function __construct(
        Request $request,
        QueryBuilderConfig $config = new QueryBuilderConfig,
    ) {
        $reflector = new ClassReflector(static::class);

        $model = $reflector->getAttribute(Model::class)?->model;

        if ($model === null) {
            throw ModelWasInvalid::missing(static::class);
        }

        if (! inspect($model)->isObjectModel()) {
            throw ModelWasInvalid::notADatabaseModel(
                static::class,
                is_object($model) ? $model::class : $model,
            );
        }

        $this->query = query($model)->select();

        new QueryApplier($reflector, $request, $config)->apply($this->query);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->forwardDecoratedCallTo($this->query, $name, $arguments);
    }
}
