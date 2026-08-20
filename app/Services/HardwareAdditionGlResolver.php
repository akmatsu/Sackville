<?php

namespace App\Services;

use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\ResponsibleDivision;
use App\Models\TdxAsset;

class HardwareAdditionGlResolver
{
    /**
     * Determine the GL code a new hardware-addition request for the given
     * division and category should be allocated to.
     *
     * Object/sub-object (segments 4-5) come from the category's configured
     * defaults. Fund/department/division (segments 1-3) are derived from
     * whichever GL code the division's existing TDX assets already use most,
     * since divisions consistently keep their assets on one GL code (shared
     * across many areawide divisions, or unique per non-areawide division).
     *
     * Returns null when the category has no defaults configured, the
     * division has no existing GL-coded assets to derive from, or the
     * resulting 5-segment GL code doesn't exist yet — all of which mean the
     * request needs manual GL assignment by Finance rather than blocking it.
     */
    public function resolve(ResponsibleDivision $division, HardwareCategory $category): ?GlCode
    {
        if ($category->default_object_code === null || $category->default_sub_object_code_id === null) {
            return null;
        }

        $referenceGlCodeId = TdxAsset::query()
            ->where('responsible_division_id', $division->id)
            ->whereNotNull('gl_code_id')
            ->select('gl_code_id')
            ->groupBy('gl_code_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('gl_code_id');

        if ($referenceGlCodeId === null) {
            return null;
        }

        $reference = GlCode::find($referenceGlCodeId);

        if ($reference === null) {
            return null;
        }

        return GlCode::query()
            ->where('fund_code', $reference->fund_code)
            ->where('department_code', $reference->department_code)
            ->where('division_id', $reference->division_id)
            ->where('object_code', $category->default_object_code)
            ->where('sub_object_code_id', $category->default_sub_object_code_id)
            ->first();
    }
}
