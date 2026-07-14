<?php

namespace App\Support\Gl;

use InvalidArgumentException;

/**
 * Builds the canonical dotted GL string from its segments.
 *
 * A GL code is valid at multiple granularities:
 *   - fund.department (department rollup)
 *   - fund.department.division (division rollup)
 *   - fund.department.division.object (budget-line rollup)
 *   - fund.department.division.object.sub_object (transaction-level)
 *
 * Segments must be present in that hierarchical order: a sub-object code
 * cannot exist without an object code, and an object code cannot exist
 * without a division.
 */
final class GlCodeString
{
    public static function build(
        string $fundCode,
        string $departmentCode,
        ?string $divisionCode = null,
        ?string $objectCode = null,
        ?string $subObjectCode = null,
    ): string {
        if ($objectCode !== null && $divisionCode === null) {
            throw new InvalidArgumentException('A GL code cannot include an object code without a division code.');
        }

        if ($subObjectCode !== null && $objectCode === null) {
            throw new InvalidArgumentException('A GL code cannot include a sub-object code without an object code.');
        }

        $segments = array_filter(
            [$fundCode, $departmentCode, $divisionCode, $objectCode, $subObjectCode],
            static fn (?string $segment): bool => $segment !== null,
        );

        return implode('.', $segments);
    }
}
