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

/**
 * Turns a class annotated with `#[Model]`, `#[AllowedFilter]`, `#[AllowedSort]`,
 * `#[DefaultSort]`, `#[AllowedInclude]` and `#[AllowedField]` into a Tempest
 * select query that is already filtered, sorted, eager loaded and narrowed
 * according to the request.
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

        $inspector = inspect($model);

        if (! $inspector->isObjectModel()) {
            throw ModelWasInvalid::notADatabaseModel(
                static::class,
                is_object($model) ? $model::class : $model,
            );
        }

        // Fields are fixed when the query is constructed, so they are resolved
        // before it exists rather than applied to it afterwards.
        $fields = new FieldsResolver($reflector, $request, $config, $inspector)->resolve();

        $this->query = new SelectQueryBuilder($model, $fields);

        new QueryApplier($reflector, $request, $config)->apply($this->query);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->forwardDecoratedCallTo($this->query, $name, $arguments);
    }
}
