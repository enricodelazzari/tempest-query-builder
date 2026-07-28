<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Includes;

use EnricoDeLazzari\QueryBuilder\Exceptions\AggregateWasInvalid;
use Tempest\Database\Builder\ModelInspector;
use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;
use Tempest\Database\Config\DatabaseDialect;
use Tempest\Database\QueryStatements\FieldStatement;

use function Tempest\Database\inspect;

/**
 * Selects an aggregate over a relation, e.g. `?include=booksCount`.
 *
 * The aggregate is computed in a derived table joined onto the query, rather
 * than in a correlated subquery in the select list: Tempest compiles every
 * selected column through `FieldStatement`, which quotes each dot separated
 * part of it, and that mangles any expression containing a qualified column.
 * A join, on the other hand, is passed through as written.
 *
 * The derived table groups the *queried* table left joined to the related one,
 * not the related table on its own, so that a row without any related record
 * still produces a group. Without it `COUNT` would come back as `null` instead
 * of `0` for exactly the rows that have nothing to count.
 */
abstract readonly class RelationAggregate implements Inclusion
{
    /**
     * Column of the derived table holding the aggregated value.
     */
    private const string VALUE = 'aggregate';

    /**
     * Column of the derived table the join matches on.
     */
    private const string KEY = 'group_key';

    public function apply(SelectQueryBuilder $query, string $relation, string $name): void
    {
        $query->join($this->join($query->model, $relation, $name));
    }

    /**
     * The column to select, which only exists once `apply` has joined the
     * derived table it reads from.
     */
    public function field(string $name): FieldStatement
    {
        return new FieldStatement(sprintf('%s.%s', self::table($name), self::VALUE))
            ->withAlias($name);
    }

    /**
     * The aggregate to compute, e.g. `COUNT(`books`.`author_id`)`.
     *
     * @param  string  $key  the qualified column joining the related table to
     *                       the queried one, which is non-null exactly for the
     *                       rows that have a related record
     * @param  string  $table  the related table the aggregate reads from
     */
    abstract protected function expression(string $key, string $table): string;

    private function join(ModelInspector $model, string $relation, string $name): string
    {
        $definition = $model->getRelation($relation);

        if ($definition === null) {
            throw AggregateWasInvalid::notARelation($model->getName(), $relation);
        }

        $exists = $definition->getExistsStatement();

        // Every relation builds this condition with the same `%s = %s` format,
        // relating the related table on the left to the queried one on the right.
        [$related, $queried] = explode(' = ', $exists->condition, 2);

        // A relation reached through a pivot or an intermediate table carries a
        // second join. Compiling it for MySQL is what leaves the backticks
        // alone; the join this method returns is translated as a whole, for
        // whichever dialect the query ends up running on.
        $through = $exists->joinStatement?->compile(DatabaseDialect::MYSQL) ?? '';

        $table = self::table($name);

        $aggregate = sprintf(
            'SELECT %s AS `%s`, %s AS `%s` FROM %s LEFT JOIN %s ON %s %s GROUP BY %s',
            $queried,
            self::KEY,
            $this->expression($related, inspect($exists->relatedModelName)->getTableName()),
            self::VALUE,
            $model->getTableName(),
            $exists->relatedTable,
            $exists->condition,
            $through,
            $queried,
        );

        return sprintf(
            'LEFT JOIN (%s) AS `%s` ON `%s`.`%s` = %s',
            preg_replace('/\s+/', ' ', $aggregate),
            $table,
            $table,
            self::KEY,
            $queried,
        );
    }

    /**
     * Name of the derived table, kept apart from the name the aggregate is
     * selected as so the two cannot collide.
     */
    private static function table(string $name): string
    {
        return $name.'_aggregate';
    }
}
