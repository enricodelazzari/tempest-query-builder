<?php

namespace EnricoDeLazzari\QueryBuilder;

use EnricoDeLazzari\QueryBuilder\Appliers\QueryApplier;
use EnricoDeLazzari\QueryBuilder\Attributes\Model;
use EnricoDeLazzari\QueryBuilder\Support\ForwardsCalls;
use Exception;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;

use function Tempest\Database\model;
use function Tempest\Database\query;

trait HasQueryBuilder
{
    use ForwardsCalls;

    private SelectQueryBuilder $query;

    public function __construct(
        private Request $request
    ) {
        $reflector = new ClassReflector(static::class);

        $model = $reflector->getAttribute(Model::class)?->name;

        if (! model($model)->isObjectModel()) {
            throw new Exception('Invalid Model.');
        }

        $this->query = query($model)->select();

        new QueryApplier(
            $reflector,
            $request,
            $this->query
        )->apply();
    }

    public function getBindings(): array
    {
        return invade($this->query)->bindings;
    }

    public function __call(string $name, array $arguments)
    {
        return $this->forwardCallTo($this->query, $name, $arguments);
    }
}
