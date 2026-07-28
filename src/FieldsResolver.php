<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder;

use EnricoDeLazzari\QueryBuilder\Attributes\AllowedField;
use EnricoDeLazzari\QueryBuilder\Exceptions\InvalidFieldQuery;
use EnricoDeLazzari\QueryBuilder\Includes\RelationAggregate;
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
        $columns = $this->columns();
        $aggregates = $this->aggregates();

        if ($aggregates === []) {
            return $columns === null ? null : arr($columns);
        }

        // An aggregate adds a column to the selection rather than narrowing it,
        // so the model's own columns have to be spelled out once something is
        // appended to them.
        return arr([...$columns ?? $this->all(), ...$aggregates]);
    }

    /**
     * The model's own columns the request narrowed the selection to, or `null`
     * when it asked for none of them.
     *
     * @return FieldStatement[]|null
     */
    private function columns(): ?array
    {
        $allowed = $this->allowed(AllowedField::class);
        $requested = $this->requested();

        $this->guard($requested, $allowed, InvalidFieldQuery::class, $this->config->strictFields);

        // Only null when strict mode is off, since `guard` threw otherwise.
        $names = array_values(array_filter(
            $requested,
            fn (string $name): bool => $this->find($allowed, $name) !== null,
        ));

        if ($names === []) {
            return null;
        }

        $table = $this->model->getTableName();

        return array_map(
            static fn (string $name): FieldStatement => new FieldStatement("{$table}.{$name}")->withAlias(),
            $names,
        );
    }

    /**
     * Every column of the model's own table, which is what Tempest selects when
     * a query is built without fields of its own.
     *
     * @return FieldStatement[]
     */
    private function all(): array
    {
        $table = $this->model->getTableName();

        return array_map(
            static fn (mixed $name): FieldStatement => new FieldStatement($table.'.'.self::stringify($name))->withAlias(),
            array_values($this->model->getSelectFields()->toArray()),
        );
    }

    /**
     * The columns holding the aggregates the request asked for.
     *
     * These cannot be applied to the query the way the rest of an include is,
     * because Tempest fixes a select query's fields when it is constructed. The
     * joins the columns read from are added later, by the includes applier.
     *
     * @return FieldStatement[]
     */
    private function aggregates(): array
    {
        $aggregates = [];

        foreach ($this->includes() as $include) {
            if ($include->include instanceof RelationAggregate) {
                $aggregates[] = $include->include->field($include->name);
            }
        }

        return $aggregates;
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
