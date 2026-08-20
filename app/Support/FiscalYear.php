<?php

namespace App\Support;

/**
 * MSB's fiscal year runs July 1 - June 30 and is referenced as a short
 * two-digit integer (FY27 = July 2026 - June 2027), matching the convention
 * used on `budget_cycles.fiscal_year`, `software_licenses.fiscal_year`, and
 * `hardware_model_costs.fiscal_year`.
 */
final class FiscalYear
{
    public static function current(): int
    {
        $now = now();
        $shortYear = (int) $now->format('y');

        return $now->month >= 7 ? $shortYear + 1 : $shortYear;
    }
}
