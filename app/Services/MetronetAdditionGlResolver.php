<?php

namespace App\Services;

use App\Jobs\SyncTdxMetronet;
use App\Models\GlCode;
use App\Models\ResponsibleDivision;
use App\Models\TdxMetronetCircuit;

class MetronetAdditionGlResolver
{
    /**
     * Object/sub-object code for Metronet circuits, matching what
     * {@see SyncTdxMetronet} resolves existing circuits to.
     */
    private const CIRCUIT_OBJECT_CODE = '421';

    private const CIRCUIT_SUB_OBJECT_CODE = '100';

    /**
     * Determine the GL code a new Metronet-circuit request for the given
     * division should be allocated to.
     *
     * Fund/department/division (segments 1-3) are derived from whichever GL
     * code the division's existing Metronet circuits already use most, since
     * a division consistently keeps its circuits on one GL code. Object/sub-
     * object (segments 4-5) are the fixed communications code every circuit
     * already syncs to.
     *
     * Returns null when the division has no existing GL-coded circuit to
     * derive from, or the resulting 5-segment GL code doesn't exist yet —
     * both of which mean the request needs manual GL assignment by Finance
     * rather than blocking it.
     */
    public function resolve(ResponsibleDivision $division): ?GlCode
    {
        $referenceGlCodeId = TdxMetronetCircuit::query()
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
            ->where('object_code', self::CIRCUIT_OBJECT_CODE)
            ->whereHas('subObjectCode', fn ($query) => $query->where('code', self::CIRCUIT_SUB_OBJECT_CODE))
            ->first();
    }
}
