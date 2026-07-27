<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedField;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFieldQuery;
use EnricoDeLazzari\QueryBuilder\Support\ReadsRequest;
use Tempest\Database\Builder\ModelInspector;
use Tempest\Database\QueryStatements\FieldStatement;
use Tempest\Http\Request;
use Tempest\Reflection\ClassReflector;
use Tempest\Support\Arr\ImmutableArray;

use function Tempest\Support\arr;

/**
 * Works out which columns the query should select.
 *
 * Unlike filters, sorts and includes, this cannot be an applier: Tempest fixes
 * a select query's fields when the query builder is constructed, so they have to
 * be known up front.
 */
final readonly class FieldsResolver
{
    use ReadsRequest;

    public function __construct(
        private ClassReflector $reflector,
        private Request $request,
        private QueryBuilderConfig $config,
        private ModelInspector $model,
    ) {}

    /**
     * Returns the fields to select, or `null` to select all of the model's own
     * columns.
     *
     * @return ImmutableArray<int, FieldStatement>|null
     */
    public function resolve(): ?ImmutableArray
    {
        $allowed = $this->allowed(AllowedField::class);
        $requested = $this->requested();

        $this->guard($requested, $allowed, InvalidFieldQuery::class);

        // Only null when strict mode is off, since `guard` threw otherwise.
        $names = array_values(array_filter(
            $requested,
            fn (string $name): bool => $this->find($allowed, $name) !== null,
        ));

        if ($names === []) {
            return null;
        }

        $table = $this->model->getTableName();

        return arr(array_map(
            static fn (string $name): FieldStatement => new FieldStatement("{$table}.{$name}")->withAlias(),
            $names,
        ));
    }

    /**
     * @return string[]
     */
    private function requested(): array
    {
        $fields = $this->parameter($this->config->fieldsParameter);

        if (! is_array($fields)) {
            return [];
        }

        // Only the model's own table can be narrowed; see the README.
        return $this->split($fields[$this->model->getTableName()] ?? '');
    }
}
