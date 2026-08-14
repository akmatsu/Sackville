<?php

use App\Enums\ResponsibilityRole;
use App\Models\BudgetCycle;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareModelCost;
use App\Models\HardwareReplacementGroup;
use App\Models\HardwareReplacementSelection;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\TdxAsset;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mobile Device Replacements')] class extends Component {
    /**
     * @var array<int, array{hardware_model_id: int|null, opted_out: bool, notes: string|null}>
     */
    public array $selections = [];

    public ?int $editingAssetId = null;

    /**
     * @var list<string>
     */
    public array $collapsedDivisions = [];

    public string $search = '';

    public string $statusFilter = 'all';

    public string $cycleFilter = 'all';

    public string $divisionFilter = '';

    public function mount(): void
    {
        foreach ($this->baseEligibleAssets as $asset) {
            $existing = $asset->replacementSelections->first();

            $this->selections[$asset->id] = [
                'hardware_model_id' => $existing?->hardware_model_id,
                'opted_out' => $existing?->opted_out ?? false,
                'notes' => $existing?->notes,
            ];
        }
    }

    #[Computed]
    public function openCycle(): ?BudgetCycle
    {
        return BudgetCycle::query()->open()->latest('fiscal_year')->first();
    }

    /**
     * All assets eligible for replacement this cycle, before search/filters
     * are applied, sorted by their full department → division → location
     * breadcrumb so groupedRows() can walk the filtered result in a single
     * pass and emit correct group boundaries.
     *
     * An asset is eligible once its fy_replacement has arrived or passed
     * (TDX's fy_replacement field isn't reliably bumped forward once an
     * asset actually gets replaced), as long as it hasn't already had a
     * real replacement model picked in an earlier cycle — an opt-out
     * doesn't count, since deferring isn't the same as being replaced.
     *
     * @return Collection<int, TdxAsset>
     */
    #[Computed]
    public function baseEligibleAssets(): Collection
    {
        $cycle = $this->openCycle;

        if (!$cycle) {
            return collect();
        }

        return TdxAsset::query()
            ->visibleTo(Auth::user())
            ->eligibleForReplacement('Mobile', $cycle)
            ->with(['model.category', 'responsibleDivision', 'responsibleLocation', 'glCode', 'plan', 'replacementSelections' => fn($query) => $query->where('budget_cycle_id', $cycle->id)])
            ->get()
            ->sortBy(fn(TdxAsset $asset): string => sprintf('%s|%s|%s|%s', $asset->assigned_department_code ?? '', $asset->responsibleDivision?->name ?? '', $asset->responsibleLocation?->name ?? '', $asset->asset_tag ?? $asset->tdx_asset_id))
            ->values();
    }

    /**
     * baseEligibleAssets(), narrowed by the current search text and filters.
     * This is what the table and its totals are built from.
     *
     * @return Collection<int, TdxAsset>
     */
    #[Computed]
    public function eligibleAssets(): Collection
    {
        return $this->baseEligibleAssets
            ->filter(fn(TdxAsset $asset): bool => $this->matchesSearch($asset) && $this->matchesStatus($asset) && $this->matchesCycleFilter($asset) && $this->matchesDivision($asset))
            ->values();
    }

    /**
     * Divisions represented among baseEligibleAssets, for the division
     * filter's options — scoped to what the user can actually see, and
     * unaffected by the other filters so switching divisions doesn't hide
     * itself from the dropdown.
     *
     * @return Collection<int, array{id: int, label: string}>
     */
    #[Computed]
    public function availableDivisions(): Collection
    {
        return $this->baseEligibleAssets
            ->pluck('responsibleDivision')
            ->filter()
            ->unique('id')
            ->map(fn(ResponsibleDivision $division): array => [
                'id' => $division->id,
                'label' => $this->breadcrumb($division->department_name, $division->name),
            ])
            ->sortBy('label')
            ->values();
    }

    protected function matchesSearch(TdxAsset $asset): bool
    {
        $needle = trim($this->search);

        if ($needle === '') {
            return true;
        }

        $needle = mb_strtolower($needle);

        foreach ([$asset->asset_tag, $asset->description, $asset->assigned_user_upn, $asset->model?->name] as $haystack) {
            if ($haystack !== null && str_contains(mb_strtolower($haystack), $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesStatus(TdxAsset $asset): bool
    {
        if ($this->statusFilter === 'all') {
            return true;
        }

        $selection = $this->selections[$asset->id] ?? ['hardware_model_id' => null, 'opted_out' => false];
        $optedOut = (bool) ($selection['opted_out'] ?? false);
        $hasModel = ($selection['hardware_model_id'] ?? null) !== null && $selection['hardware_model_id'] !== '';

        return match ($this->statusFilter) {
            'pending' => !$optedOut && !$hasModel,
            'selected' => !$optedOut && $hasModel,
            'opted_out' => $optedOut,
            default => true,
        };
    }

    protected function matchesCycleFilter(TdxAsset $asset): bool
    {
        if ($this->cycleFilter === 'all') {
            return true;
        }

        $cycle = $this->openCycle;

        if (!$cycle || $asset->fy_replacement === null) {
            return true;
        }

        $isOverdue = $asset->fy_replacement < $cycle->fiscal_year;

        return $this->cycleFilter === 'overdue' ? $isOverdue : !$isOverdue;
    }

    protected function matchesDivision(TdxAsset $asset): bool
    {
        if ($this->divisionFilter === '') {
            return true;
        }

        return $asset->responsible_division_id === (int) $this->divisionFilter;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->cycleFilter = 'all';
        $this->divisionFilter = '';
    }

    /**
     * Active replacement models eligible under the Mobile replacement group(s).
     *
     * @return Collection<int, \App\Models\HardwareModel>
     */
    #[Computed]
    public function eligibleModels(): Collection
    {
        $category = HardwareCategory::query()->where('name', 'Mobile')->with('hardwareReplacementGroups.eligibleModels')->first();

        if (!$category) {
            return collect();
        }

        return $category->hardwareReplacementGroups->filter(fn(HardwareReplacementGroup $group): bool => $group->active)->flatMap(fn(HardwareReplacementGroup $group) => $group->eligibleModels)->filter(fn($model): bool => $model->active)->unique('id')->sortBy('name')->values();
    }

    /**
     * Reference-only catalog unit costs for the open cycle's fiscal year, keyed
     * by "{modelId}:{withDocking}". Covers both the eligible replacement models
     * and every model currently assigned to an eligible asset, so it serves
     * both the current-cost and replacement-cost columns from one lookup.
     *
     * @return Collection<string, HardwareModelCost>
     */
    #[Computed]
    public function modelCosts(): Collection
    {
        $cycle = $this->openCycle;

        if (!$cycle) {
            return collect();
        }

        $modelIds = $this->eligibleModels
            ->pluck('id')
            ->merge($this->baseEligibleAssets->pluck('hardware_model_id')->filter())
            ->unique();

        return HardwareModelCost::query()->whereIn('hardware_model_id', $modelIds)->where('fiscal_year', $cycle->fiscal_year)->get()->keyBy(fn(HardwareModelCost $cost): string => $cost->hardware_model_id . ':' . ($cost->with_docking ? '1' : '0'));
    }

    /**
     * The current model's reference unit cost. Mobile devices have no
     * docking-station concept, so this always looks up the base cost row.
     */
    protected function currentCostFor(TdxAsset $asset): ?float
    {
        if ($asset->hardware_model_id === null) {
            return null;
        }

        return $this->modelCostFor($asset->hardware_model_id, false);
    }

    /**
     * The in-progress (not-yet-saved) replacement selection's cost, so totals
     * update live as a manager picks a model.
     */
    protected function replacementCostFor(TdxAsset $asset): ?float
    {
        $selection = $this->selections[$asset->id] ?? null;
        $modelId = $selection['hardware_model_id'] ?? null;

        if ($modelId === null) {
            return null;
        }

        return $this->modelCostFor($modelId, false);
    }

    protected function modelCostFor(int $hardwareModelId, bool $withDocking): ?float
    {
        $cost = $this->modelCosts->get($hardwareModelId . ':' . ($withDocking ? '1' : '0'));

        if ($cost !== null) {
            return (float) $cost->unit_cost;
        }

        if ($withDocking) {
            $baseCost = $this->modelCosts->get($hardwareModelId . ':0');

            if ($baseCost !== null) {
                return (float) $baseCost->unit_cost + (float) ($baseCost->docking_upcharge ?? 0);
            }
        }

        return null;
    }

    /**
     * @return array{current: float, replacement: float, pending: int}
     */
    protected function zeroTotals(): array
    {
        return ['current' => 0.0, 'replacement' => 0.0, 'pending' => 0];
    }

    /**
     * @param  array{current: float, replacement: float, pending: int}  $totals
     */
    protected function accumulate(array &$totals, ?float $currentCost, ?float $replacementCost, bool $pending): void
    {
        $totals['current'] += $currentCost ?? 0.0;
        $totals['replacement'] += $replacementCost ?? 0.0;
        $totals['pending'] += $pending ? 1 : 0;
    }

    protected function breadcrumb(?string ...$parts): string
    {
        return implode(' — ', array_filter($parts, fn(?string $part): bool => $part !== null && $part !== ''));
    }

    /**
     * Flattened, ordered rows for the replacements table: a header row when
     * entering a new division/location group, one row per asset, a subtotal
     * row when leaving a group (innermost first), and a grand total row at
     * the very end. Kept flat (rather than nested loops in the Blade
     * template) so the table stays a single @foreach with a row-type switch.
     *
     * Division rows fold the department into their label (division names
     * are unique on their own, but showing the department they belong to
     * gives directors the rollup context a separate department row used to
     * provide) and carry a `division_key` so the template can hide their
     * location/asset rows when the division is collapsed.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function groupedRows(): array
    {
        $rows = [];

        $currentDivisionKey = null;
        $currentLocationKey = null;

        $lastDivisionLabel = null;
        $lastLocationLabel = null;

        $divisionTotals = $this->zeroTotals();
        $locationTotals = $this->zeroTotals();
        $grandTotals = $this->zeroTotals();

        foreach ($this->eligibleAssets as $asset) {
            $departmentLabel = $asset->responsibleDivision?->department_name ?? $asset->assigned_department_code ?? __('No department');
            $divisionName = $asset->responsibleDivision?->name ?? __('No division');
            $divisionLabel = $this->breadcrumb($departmentLabel, $divisionName);
            $divisionKey = $asset->responsible_division_id !== null ? 'division:' . $asset->responsible_division_id : 'department:' . ($asset->assigned_department_code ?? '');

            $locationLabel = $asset->responsibleLocation?->name;
            $locationKey = $asset->responsible_location_id !== null ? 'location:' . $asset->responsible_location_id : null;

            if ($divisionKey !== $currentDivisionKey) {
                if ($currentLocationKey !== null) {
                    $rows[] = ['type' => 'subtotal', 'depth' => 1, 'division_key' => $currentDivisionKey, 'hide_when_collapsed' => true, 'label' => $this->breadcrumb($lastDivisionLabel, $lastLocationLabel), ...$locationTotals];
                    $locationTotals = $this->zeroTotals();
                    $currentLocationKey = null;
                }
                if ($currentDivisionKey !== null) {
                    $rows[] = ['type' => 'subtotal', 'depth' => 0, 'division_key' => $currentDivisionKey, 'label' => $lastDivisionLabel, ...$divisionTotals];
                    $divisionTotals = $this->zeroTotals();
                }

                $currentDivisionKey = $divisionKey;
                $rows[] = ['type' => 'header', 'depth' => 0, 'division_key' => $divisionKey, 'label' => $divisionLabel];
            }

            if ($locationKey !== $currentLocationKey) {
                if ($currentLocationKey !== null) {
                    $rows[] = ['type' => 'subtotal', 'depth' => 1, 'division_key' => $divisionKey, 'hide_when_collapsed' => true, 'label' => $this->breadcrumb($lastDivisionLabel, $lastLocationLabel), ...$locationTotals];
                    $locationTotals = $this->zeroTotals();
                }

                $currentLocationKey = $locationKey;

                if ($locationKey !== null) {
                    $rows[] = ['type' => 'header', 'depth' => 1, 'division_key' => $divisionKey, 'hide_when_collapsed' => true, 'label' => $this->breadcrumb($divisionLabel, $locationLabel)];
                }
            }

            $currentCost = $this->currentCostFor($asset);
            $replacementCost = $this->replacementCostFor($asset);
            $optedOut = (bool) ($this->selections[$asset->id]['opted_out'] ?? false);
            $isPending = !$optedOut && $replacementCost === null;

            $rows[] = ['type' => 'asset', 'division_key' => $divisionKey, 'hide_when_collapsed' => true, 'asset' => $asset, 'current_cost' => $currentCost, 'replacement_cost' => $replacementCost, 'opted_out' => $optedOut];

            $this->accumulate($locationTotals, $currentCost, $replacementCost, $isPending);
            $this->accumulate($divisionTotals, $currentCost, $replacementCost, $isPending);
            $this->accumulate($grandTotals, $currentCost, $replacementCost, $isPending);

            $lastDivisionLabel = $divisionLabel;
            $lastLocationLabel = $locationLabel;
        }

        if ($currentLocationKey !== null) {
            $rows[] = ['type' => 'subtotal', 'depth' => 1, 'division_key' => $currentDivisionKey, 'hide_when_collapsed' => true, 'label' => $this->breadcrumb($lastDivisionLabel, $lastLocationLabel), ...$locationTotals];
        }
        if ($currentDivisionKey !== null) {
            $rows[] = ['type' => 'subtotal', 'depth' => 0, 'division_key' => $currentDivisionKey, 'label' => $lastDivisionLabel, ...$divisionTotals];
        }

        if ($rows !== []) {
            $rows[] = ['type' => 'grand_total', 'label' => __('Grand total'), ...$grandTotals];
        }

        return $rows;
    }

    public function toggleDivision(string $divisionKey): void
    {
        if (in_array($divisionKey, $this->collapsedDivisions, true)) {
            $this->collapsedDivisions = array_values(array_diff($this->collapsedDivisions, [$divisionKey]));

            return;
        }

        $this->collapsedDivisions[] = $divisionKey;
    }

    public function canEdit(TdxAsset $asset): bool
    {
        return Auth::user()->responsibilities->filter(fn(Responsibility $responsibility): bool => $responsibility->matchesAsset($asset))->contains(fn(Responsibility $responsibility): bool => in_array($responsibility->role, [ResponsibilityRole::Edit, ResponsibilityRole::Admin], true));
    }

    #[Computed]
    public function editingAsset(): ?TdxAsset
    {
        return $this->editingAssetId !== null ? $this->baseEligibleAssets->firstWhere('id', $this->editingAssetId) : null;
    }

    public function edit(int $tdxAssetId): void
    {
        $asset = $this->baseEligibleAssets->firstWhere('id', $tdxAssetId);

        abort_unless($asset && $this->canEdit($asset), 403);

        $this->editingAssetId = $tdxAssetId;
    }

    public function stopEditing(): void
    {
        $this->editingAssetId = null;
    }

    public function save(int $tdxAssetId): void
    {
        $asset = $this->baseEligibleAssets->firstWhere('id', $tdxAssetId);
        $cycle = $this->openCycle;

        abort_unless($asset && $cycle && $this->canEdit($asset), 403);

        $optedOut = (bool) ($this->selections[$tdxAssetId]['opted_out'] ?? false);

        $validated = validator($this->selections[$tdxAssetId] ?? [], [
            'hardware_model_id' => [$optedOut ? 'nullable' : 'required', Rule::in($this->eligibleModels->pluck('id'))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $model = $optedOut ? null : $this->eligibleModels->firstWhere('id', $validated['hardware_model_id']);

        HardwareReplacementSelection::updateOrCreate(
            ['budget_cycle_id' => $cycle->id, 'tdx_asset_id' => $asset->id],
            [
                'hardware_model_id' => $model?->id,
                'opted_out' => $optedOut,
                'with_docking' => false,
                'notes' => $validated['notes'] ?? null,
                'selected_by_id' => Auth::id(),
            ],
        );

        unset($this->eligibleAssets, $this->baseEligibleAssets);
        $this->editingAssetId = null;
        Flux::modal('edit-replacement')->close();

        Flux::toast(variant: 'success', text: __('Replacement selection saved.'));
    }

    public function clear(int $tdxAssetId): void
    {
        $asset = $this->baseEligibleAssets->firstWhere('id', $tdxAssetId);
        $cycle = $this->openCycle;

        abort_unless($asset && $cycle && $this->canEdit($asset), 403);

        HardwareReplacementSelection::query()->where('budget_cycle_id', $cycle->id)->where('tdx_asset_id', $asset->id)->delete();

        $this->selections[$tdxAssetId] = [
            'hardware_model_id' => null,
            'opted_out' => false,
            'notes' => null,
        ];

        unset($this->eligibleAssets, $this->baseEligibleAssets);
        $this->editingAssetId = null;
        Flux::modal('edit-replacement')->close();

        Flux::toast(variant: 'success', text: __('Selection cleared.'));
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Mobile Device Replacements') }}</flux:heading>
    <flux:subheading>
        {{ __('Select a replacement model for mobile devices due for replacement in your area this budget cycle.') }}
    </flux:subheading>

    <div class="mt-6">
        @if (!$this->openCycle)
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.heading>{{ __('No open budget cycle') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Mobile device replacements can only be selected while a budget cycle is open.') }}
                </flux:callout.text>
            </flux:callout>
        @elseif ($this->baseEligibleAssets->isEmpty())
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.heading>{{ __('Nothing to replace right now') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('No mobile devices in your area are flagged for replacement in this budget cycle.') }}
                </flux:callout.text>
            </flux:callout>
        @else
            <div class="mb-4 flex flex-wrap items-end gap-4">
                <div class="min-w-64 flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                        :placeholder="__('Search asset tag, description, assigned user, or model...')" />
                </div>
                <flux:select wire:model.live="statusFilter" class="w-44" :label="__('Status')">
                    <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                    <flux:select.option value="selected">{{ __('Selected') }}</flux:select.option>
                    <flux:select.option value="opted_out">{{ __('Opted out') }}</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="cycleFilter" class="w-44" :label="__('Cycle')">
                    <flux:select.option value="all">{{ __('All items') }}</flux:select.option>
                    <flux:select.option value="overdue">{{ __('Carried over') }}</flux:select.option>
                    <flux:select.option value="current">{{ __('Due this cycle') }}</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="divisionFilter" class="w-56" :label="__('Division')">
                    <flux:select.option value="">{{ __('All divisions') }}</flux:select.option>
                    @foreach ($this->availableDivisions as $division)
                        <flux:select.option value="{{ $division['id'] }}">{{ $division['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($this->search !== '' || $this->statusFilter !== 'all' || $this->cycleFilter !== 'all' || $this->divisionFilter !== '')
                    <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                        {{ __('Clear filters') }}
                    </flux:button>
                @endif
            </div>

            @if ($this->eligibleAssets->isEmpty())
                <flux:callout icon="magnifying-glass" variant="secondary">
                    <flux:callout.heading>{{ __('No matches') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('No mobile devices match your search or filters.') }}
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Asset') }}</flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column>{{ __('FY Replacement') }}</flux:table.column>
                    <flux:table.column>{{ __('Assigned to') }}</flux:table.column>
                    <flux:table.column>{{ __('Carrier') }}</flux:table.column>
                    <flux:table.column>{{ __('Current cost') }}</flux:table.column>
                    <flux:table.column>{{ __('Replacement model') }}</flux:table.column>
                    <flux:table.column>{{ __('Plan status') }}</flux:table.column>
                    <flux:table.column>{{ __('Replacement cost') }}</flux:table.column>
                    <flux:table.column>{{ __('Notes') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->groupedRows as $row)
                        @continue(($row['hide_when_collapsed'] ?? false) && in_array($row['division_key'], $this->collapsedDivisions, true))

                        @if ($row['type'] === 'header' && $row['depth'] === 0)
                            <flux:table.row>
                                <flux:table.cell colspan="11"
                                    class="border-t border-zinc-200 bg-zinc-100 py-1 dark:border-zinc-700 dark:bg-zinc-800">
                                    <button type="button" wire:click="toggleDivision('{{ $row['division_key'] }}')"
                                        class="flex w-full cursor-pointer items-center gap-2 text-left">
                                        @if (in_array($row['division_key'], $this->collapsedDivisions, true))
                                            <flux:icon.chevron-right
                                                class="size-4 shrink-0 text-zinc-500 dark:text-zinc-400" />
                                        @else
                                            <flux:icon.chevron-down
                                                class="size-4 shrink-0 text-zinc-500 dark:text-zinc-400" />
                                        @endif
                                        <span
                                            class="font-semibold text-zinc-900 dark:text-white">{{ $row['label'] }}</span>
                                    </button>
                                </flux:table.cell>
                            </flux:table.row>
                        @elseif ($row['type'] === 'header')
                            <flux:table.row>
                                <flux:table.cell colspan="11"
                                    class="bg-zinc-50 pl-8 text-sm font-medium text-zinc-500 dark:bg-zinc-900/40 dark:text-zinc-400">
                                    {{ $row['label'] }}
                                </flux:table.cell>
                            </flux:table.row>
                        @elseif ($row['type'] === 'asset')
                            @php $asset = $row['asset']; @endphp
                            <flux:table.row :key="$asset->id">
                                <flux:table.cell>
                                    <div class="font-medium">{{ $asset->asset_tag ?? $asset->tdx_asset_id }}</div>
                                    <flux:text size="sm">{{ $asset->model?->name }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">{{ $asset->description ?? '—' }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($asset->fy_replacement !== null && $asset->fy_replacement < $this->openCycle->fiscal_year)
                                        <flux:badge size="sm" color="amber">FY{{ $asset->fy_replacement }}</flux:badge>
                                    @else
                                        <flux:text size="sm">FY{{ $asset->fy_replacement }}</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">{{ $asset->assigned_user_upn ?? '—' }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">{{ $asset->plan?->carrier ?? '—' }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">
                                        {{ $row['current_cost'] !== null ? '$' . number_format($row['current_cost'], 2) : '—' }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">
                                        @if ($row['opted_out'])
                                            {{ __('No replacement needed') }}
                                        @else
                                            {{ $this->eligibleModels->firstWhere('id', $this->selections[$asset->id]['hardware_model_id'] ?? null)?->name ?? __('Not selected') }}
                                        @endif
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">{{ $asset->plan?->plan_status ?? '—' }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">
                                        {{ $row['replacement_cost'] !== null ? '$' . number_format($row['replacement_cost'], 2) : '—' }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm" class="line-clamp-2">
                                        {{ $this->selections[$asset->id]['notes'] ?? '—' }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($this->canEdit($asset))
                                        <flux:modal.trigger name="edit-replacement">
                                            <flux:button wire:click="edit({{ $asset->id }})" size="sm">
                                                {{ __('Edit') }}
                                            </flux:button>
                                        </flux:modal.trigger>
                                    @else
                                        <flux:badge size="sm">{{ __('View only') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @elseif ($row['type'] === 'subtotal')
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-right font-medium"
                                    :style="'padding-left: '.($row['depth'] * 1.5).
                                    'rem'">
                                    {{ $row['label'] }} {{ __('subtotal') }}
                                    @if ($row['pending'] > 0)
                                        <flux:text size="sm" class="inline">
                                            ({{ trans_choice('{1} :count pending|[2,*] :count pending', $row['pending'], ['count' => $row['pending']]) }})
                                        </flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-medium">${{ number_format($row['current'], 2) }}
                                </flux:table.cell>
                                <flux:table.cell colspan="2"></flux:table.cell>
                                <flux:table.cell class="font-medium">${{ number_format($row['replacement'], 2) }}
                                </flux:table.cell>
                                <flux:table.cell colspan="2"></flux:table.cell>
                            </flux:table.row>
                        @elseif ($row['type'] === 'grand_total')
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-right font-semibold">
                                    {{ $row['label'] }}
                                    @if ($row['pending'] > 0)
                                        <flux:text size="sm" class="inline">
                                            ({{ trans_choice('{1} :count pending|[2,*] :count pending', $row['pending'], ['count' => $row['pending']]) }})
                                        </flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-semibold">${{ number_format($row['current'], 2) }}
                                </flux:table.cell>
                                <flux:table.cell colspan="2"></flux:table.cell>
                                <flux:table.cell class="font-semibold">${{ number_format($row['replacement'], 2) }}
                                </flux:table.cell>
                                <flux:table.cell colspan="2"></flux:table.cell>
                            </flux:table.row>
                        @endif
                    @endforeach
                </flux:table.rows>
                </flux:table>
            @endif

            <flux:modal name="edit-replacement" class="max-w-lg" @close="stopEditing">
                @if ($asset = $this->editingAsset)
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ $asset->asset_tag ?? $asset->tdx_asset_id }}</flux:heading>
                            <flux:subheading>{{ $asset->model?->name }}</flux:subheading>
                        </div>

                        <flux:select wire:model.live="selections.{{ $asset->id }}.hardware_model_id"
                            :label="__('Replacement model')"
                            :disabled="$this->selections[$asset->id]['opted_out'] ?? false">
                            <flux:select.option value="" class="placeholder">
                                {{ __('None selected') }}
                            </flux:select.option>
                            @foreach ($this->eligibleModels as $model)
                                <flux:select.option value="{{ $model->id }}">
                                    {{ $model->name }}
                                    @if ($cost = $this->modelCosts->get($model->id . ':0'))
                                        — ${{ number_format($cost->unit_cost, 2) }}
                                    @endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:checkbox wire:model.live="selections.{{ $asset->id }}.opted_out"
                            :label="__('No replacement needed')" />

                        <flux:textarea wire:model="selections.{{ $asset->id }}.notes" :label="__('Notes')"
                            placeholder="{{ __('Optional') }}" rows="3" />

                        <div class="flex items-center justify-between gap-2">
                            <div>
                                @if ($asset->replacementSelections->isNotEmpty())
                                    <flux:button wire:click="clear({{ $asset->id }})" variant="ghost">
                                        {{ __('Clear selection') }}
                                    </flux:button>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <flux:modal.close>
                                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button wire:click="save({{ $asset->id }})" variant="primary">
                                    {{ __('Save') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endif
            </flux:modal>
        @endif
    </div>
</section>
