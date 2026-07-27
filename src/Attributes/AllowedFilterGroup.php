<?php

declare(strict_types=1);

namespace EnricoDeLazzari\QueryBuilder\Attributes;

use Attribute;
use EnricoDeLazzari\QueryBuilder\Exceptions\FilterGroupWasInvalid;

/**
 * Fans one query parameter out over several filters, joined to each other by a
 * conjunction: `?filter[q]=John` can search a name and a full name at once.
 *
 * Groups are joined to each other, and to plain filters, with `AND`.
 *
 * A member contributes its column and its filter, and splits the group's value
 * with its own `delimiter` and `ignore`. A member's `default` and `nullable`
 * have no meaning here: they describe whether a parameter was asked for, which
 * is the group's business rather than the member's.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AllowedFilterGroup implements Allowed
{
    /**
     * @param  AllowedFilter[]  $members
     */
    public function __construct(
        /**
         * Name exposed in the query string.
         */
        public string $name,

        /**
         * The filters the value is handed to.
         */
        public array $members,

        public Conjunction $conjunction = Conjunction::OR,
    ) {
        if ($this->members === []) {
            throw FilterGroupWasInvalid::withoutMembers($this->name);
        }

        foreach ($this->members as $member) {
            if (! $member instanceof AllowedFilter) {
                throw FilterGroupWasInvalid::withAForeignMember($this->name);
            }
        }
    }
}
