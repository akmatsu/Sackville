<?php

use App\Enums\BudgetLineItemStatus;
use App\Enums\BudgetLineItemType;
use App\Enums\NetworkRequestSource;
use App\Enums\ResponsibilityRole;
use App\Enums\ResponsibilityScopeType;
use App\Models\BudgetCycle;
use App\Models\BudgetLineItem;
use App\Models\GlCode;
use App\Models\LineItemGlAllocation;
use App\Models\MetronetCircuitReview;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\TdxMetronetCircuit;
use App\Services\MetronetAdditionGlResolver;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Metronet Circuit Review')] class extends Component {
    /**
     * @var array<int, array{still_needed: string, justification: string|null}>
     */
    public array $reviews = [];

    public ?int $editingCircuitId = null;

    /**
     * @var list<string>
     */
    public array $collapsedDivisions = [];

    public string $search = '';

    public string $statusFilter = 'all';

    public string $divisionFilter = '';

    /**
     * @var array{responsible_division_id: int|string|null, location: string|null, justification: string|null}
     */
    public array $newRequest = [
        'responsible_division_id' => null,
        'location' => null,
        'justification' => null,
    ];

    public ?int $editingRequestId = null;

    public function mount(): void
    {
        foreach ($this->baseReviewableCircuits as $circuit) {
            $existing = $circuit->reviews->first();

            $this->reviews[$circuit->id] = [
                'still_needed' => $existing !== null ? ($existing->still_needed ? '1' : '0') : '',
                'justification' => $existing?->justification,
            ];
        }
    }

    #[Computed]
    public function openCycle(): ?BudgetCycle
    {
        return BudgetCycle::query()->open()->latest('fiscal_year')->first();
    }

    /**
     * All circuits needing a review decision this cycle, before search/filters
     * are applied, sorted by their full department → division → location
     * breadcrumb so groupedRows() can walk the filtered result in a single
     * pass and emit correct group boundaries.
     *
     * @return Collection<int, TdxMetronetCircuit>
     */
    #[Computed]
    public function baseReviewableCircuits(): Collection
    {
        $cycle = $this->openCycle;

        if (!$cycle) {
            return collect();
        }

        return TdxMetronetCircuit::query()
            ->visibleTo(Auth::user())
            ->reviewable()
            ->with(['responsibleDivision', 'responsibleLocation', 'glCode', 'currentCost', 'reviews' => fn($query) => $query->where('budget_cycle_id', $cycle->id)])
            ->get()
            ->sortBy(fn(TdxMetronetCircuit $circuit): string => sprintf('%s|%s|%s|%s', $circuit->assigned_department_code ?? '', $circuit->responsibleDivision?->name ?? '', $circuit->responsibleLocation?->name ?? '', $circuit->location_name ?? $circuit->tdx_asset_id))
            ->values();
    }

    /**
     * baseReviewableCircuits(), narrowed by the current search text and
     * filters. This is what the table and its grouped rows are built from.
     *
     * @return Collection<int, TdxMetronetCircuit>
     */
    #[Computed]
    public function reviewableCircuits(): Collection
    {
        return $this->baseReviewableCircuits
            ->filter(fn(TdxMetronetCircuit $circuit): bool => $this->matchesSearch($circuit) && $this->matchesStatus($circuit) && $this->matchesDivision($circuit))
            ->values();
    }

    /**
     * Divisions represented among baseReviewableCircuits, for the division
     * filter's options — scoped to what the user can actually see, and
     * unaffected by the other filters so switching divisions doesn't hide
     * itself from the dropdown.
     *
     * @return Collection<int, array{id: int, label: string}>
     */
    #[Computed]
    public function availableDivisions(): Collection
    {
        return $this->baseReviewableCircuits
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

    protected function matchesSearch(TdxMetronetCircuit $circuit): bool
    {
        $needle = trim($this->search);

        if ($needle === '') {
            return true;
        }

        $needle = mb_strtolower($needle);

        foreach ([$circuit->location_name, $circuit->circuit_number, $circuit->status, $circuit->tdx_asset_id] as $haystack) {
            if ($haystack !== null && str_contains(mb_strtolower($haystack), $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesStatus(TdxMetronetCircuit $circuit): bool
    {
        if ($this->statusFilter === 'all') {
            return true;
        }

        $decision = $this->reviews[$circuit->id]['still_needed'] ?? '';

        return match ($this->statusFilter) {
            'pending' => $decision === '',
            'keeping' => $decision === '1',
            'discontinuing' => $decision === '0',
            default => true,
        };
    }

    protected function matchesDivision(TdxMetronetCircuit $circuit): bool
    {
        if ($this->divisionFilter === '') {
            return true;
        }

        return $circuit->responsible_division_id === (int) $this->divisionFilter;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->divisionFilter = '';
    }

    protected function breadcrumb(?string ...$parts): string
    {
        return implode(' — ', array_filter($parts, fn(?string $part): bool => $part !== null && $part !== ''));
    }

    /**
     * Builds the review table's rows in a single pass over
     * reviewableCircuits: a header row when entering a new division/location
     * group, and one row per circuit. Division rows fold the department into
     * their label and carry a `division_key` so the template can hide their
     * location/circuit rows when the division is collapsed.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function groupedRows(): array
    {
        $rows = [];

        $currentDivisionKey = null;
        $currentLocationKey = null;

        foreach ($this->reviewableCircuits as $circuit) {
            $departmentLabel = $circuit->responsibleDivision?->department_name ?? $circuit->assigned_department_code ?? __('No department');
            $divisionName = $circuit->responsibleDivision?->name ?? __('No division');
            $divisionLabel = $this->breadcrumb($departmentLabel, $divisionName);
            $divisionKey = $circuit->responsible_division_id !== null ? 'division:' . $circuit->responsible_division_id : 'department:' . ($circuit->assigned_department_code ?? '');

            $locationLabel = $circuit->responsibleLocation?->name;
            $locationKey = $circuit->responsible_location_id !== null ? 'location:' . $circuit->responsible_location_id : null;

            if ($divisionKey !== $currentDivisionKey) {
                $currentDivisionKey = $divisionKey;
                $currentLocationKey = null;
                $rows[] = ['type' => 'header', 'depth' => 0, 'division_key' => $divisionKey, 'label' => $divisionLabel];
            }

            if ($locationKey !== $currentLocationKey) {
                $currentLocationKey = $locationKey;

                if ($locationKey !== null) {
                    $rows[] = ['type' => 'header', 'depth' => 1, 'division_key' => $divisionKey, 'hide_when_collapsed' => true, 'label' => $this->breadcrumb($divisionLabel, $locationLabel)];
                }
            }

            $rows[] = ['type' => 'circuit', 'division_key' => $divisionKey, 'hide_when_collapsed' => true, 'circuit' => $circuit];
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

    public function canEdit(TdxMetronetCircuit $circuit): bool
    {
        return Auth::user()->responsibilities->filter(fn(Responsibility $responsibility): bool => $responsibility->matchesMetronetCircuit($circuit))->contains(fn(Responsibility $responsibility): bool => in_array($responsibility->role, [ResponsibilityRole::Edit, ResponsibilityRole::Admin], true));
    }

    #[Computed]
    public function editingCircuit(): ?TdxMetronetCircuit
    {
        return $this->editingCircuitId !== null ? $this->baseReviewableCircuits->firstWhere('id', $this->editingCircuitId) : null;
    }

    public function edit(int $circuitId): void
    {
        $circuit = $this->baseReviewableCircuits->firstWhere('id', $circuitId);

        abort_unless($circuit && $this->canEdit($circuit), 403);

        $this->editingCircuitId = $circuitId;
    }

    public function stopEditing(): void
    {
        $this->editingCircuitId = null;
    }

    public function save(int $circuitId): void
    {
        $circuit = $this->baseReviewableCircuits->firstWhere('id', $circuitId);
        $cycle = $this->openCycle;

        abort_unless($circuit && $cycle && $this->canEdit($circuit), 403);

        $validated = validator($this->reviews[$circuitId] ?? [], [
            'still_needed' => ['required', Rule::in(['0', '1'])],
            'justification' => ['required_if:still_needed,1', 'nullable', 'string', 'max:2000'],
        ])->validate();

        $stillNeeded = $validated['still_needed'] === '1';

        MetronetCircuitReview::updateOrCreate(
            ['budget_cycle_id' => $cycle->id, 'tdx_metronet_circuit_id' => $circuit->id],
            [
                'still_needed' => $stillNeeded,
                'justification' => $stillNeeded ? $validated['justification'] : null,
                'reviewed_by_id' => Auth::id(),
            ],
        );

        $this->reviews[$circuitId]['justification'] = $stillNeeded ? $validated['justification'] : null;

        unset($this->reviewableCircuits, $this->baseReviewableCircuits);
        $this->editingCircuitId = null;
        Flux::modal('edit-review')->close();

        Flux::toast(variant: 'success', text: __('Review saved.'));
    }

    public function clear(int $circuitId): void
    {
        $circuit = $this->baseReviewableCircuits->firstWhere('id', $circuitId);
        $cycle = $this->openCycle;

        abort_unless($circuit && $cycle && $this->canEdit($circuit), 403);

        MetronetCircuitReview::query()->where('budget_cycle_id', $cycle->id)->where('tdx_metronet_circuit_id', $circuit->id)->delete();

        $this->reviews[$circuitId] = [
            'still_needed' => '',
            'justification' => null,
        ];

        unset($this->reviewableCircuits, $this->baseReviewableCircuits);
        $this->editingCircuitId = null;
        Flux::modal('edit-review')->close();

        Flux::toast(variant: 'success', text: __('Review cleared.'));
    }

    /**
     * Divisions the user has Edit/Admin responsibility over directly, so a
     * division with zero existing circuits can still request a brand-new
     * one at a brand-new location. This is both the "Request a new circuit"
     * division dropdown's source and its authorization boundary.
     *
     * @return Collection<int, ResponsibleDivision>
     */
    #[Computed]
    public function requestableDivisions(): Collection
    {
        return Auth::user()->responsibilities()->with('responsibleDivision')->get()
            ->filter(fn(Responsibility $responsibility): bool => $responsibility->scope_type === ResponsibilityScopeType::Division && $responsibility->responsible_division_id !== null && in_array($responsibility->role, [ResponsibilityRole::Edit, ResponsibilityRole::Admin], true))
            ->pluck('responsibleDivision')
            ->filter()
            ->unique('id')
            ->sortBy(fn(ResponsibleDivision $division): string => $this->breadcrumb($division->department_name, $division->name))
            ->values();
    }

    /**
     * New-circuit (network) requests visible to the user this cycle: their
     * own, plus anyone else's within their Responsibility scope, mirroring
     * how the review table above rolls up across a scope rather than
     * showing only the current user's picks.
     *
     * @return Collection<int, BudgetLineItem>
     */
    #[Computed]
    public function myRequests(): Collection
    {
        $cycle = $this->openCycle;

        if (!$cycle) {
            return collect();
        }

        return BudgetLineItem::query()
            ->visibleTo(Auth::user())
            ->where('budget_cycle_id', $cycle->id)
            ->where('item_type', BudgetLineItemType::Network)
            ->where('network_source', NetworkRequestSource::Metronet)
            ->with(['responsibleDivision', 'glAllocations.glCode', 'createdBy'])
            ->latest('created_at')
            ->get();
    }

    /**
     * The GL code a new-circuit request for $divisionId would resolve to,
     * for read-only display in the request modal. Not persisted here.
     */
    public function resolvedGlCode(mixed $divisionId): ?GlCode
    {
        $division = $this->requestableDivisions->firstWhere('id', (int) $divisionId);

        if (!$division) {
            return null;
        }

        return app(MetronetAdditionGlResolver::class)->resolve($division);
    }

    public function openNewRequest(): void
    {
        abort_unless($this->requestableDivisions->isNotEmpty(), 403);

        $this->editingRequestId = null;
        $this->newRequest = [
            'responsible_division_id' => null,
            'location' => null,
            'justification' => null,
        ];
    }

    public function editRequest(int $requestId): void
    {
        $item = $this->myRequests->firstWhere('id', $requestId);

        abort_unless($item && $item->created_by_id === Auth::id() && $item->status === BudgetLineItemStatus::NotStarted, 403);

        $this->editingRequestId = $requestId;
        $this->newRequest = [
            'responsible_division_id' => $item->responsible_division_id,
            'location' => $item->description,
            'justification' => $item->justification,
        ];
    }

    public function saveRequest(): void
    {
        $cycle = $this->openCycle;

        abort_unless($cycle, 403);

        $existing = $this->editingRequestId !== null ? $this->myRequests->firstWhere('id', $this->editingRequestId) : null;

        abort_unless(
            $existing === null || ($existing->created_by_id === Auth::id() && $existing->status === BudgetLineItemStatus::NotStarted),
            403
        );

        $validated = validator($this->newRequest, [
            'responsible_division_id' => ['required', Rule::in($this->requestableDivisions->pluck('id'))],
            'location' => ['required', 'string', 'max:255'],
            'justification' => ['required', 'string', 'max:2000'],
        ])->validate();

        $division = $this->requestableDivisions->firstWhere('id', (int) $validated['responsible_division_id']);

        $attributes = [
            'budget_cycle_id' => $cycle->id,
            'responsible_division_id' => $division->id,
            'item_type' => BudgetLineItemType::Network,
            'network_source' => NetworkRequestSource::Metronet,
            'description' => $validated['location'],
            'justification' => $validated['justification'],
        ];

        if ($existing !== null) {
            $existing->update([...$attributes, 'last_modified_by_id' => Auth::id()]);
            $item = $existing;
        } else {
            $item = BudgetLineItem::create([...$attributes, 'status' => BudgetLineItemStatus::NotStarted, 'created_by_id' => Auth::id()]);
        }

        $glCode = $division !== null ? app(MetronetAdditionGlResolver::class)->resolve($division) : null;

        if ($glCode !== null) {
            LineItemGlAllocation::updateOrCreate(
                ['budget_line_item_id' => $item->id],
                ['gl_code_id' => $glCode->id, 'percent' => 100, 'amount' => 0],
            );
        } else {
            $item->glAllocations()->delete();
        }

        unset($this->myRequests);
        $this->editingRequestId = null;
        Flux::modal('request-new-circuit')->close();

        Flux::toast(variant: 'success', text: __('Circuit request saved.'));
    }

    public function deleteRequest(int $requestId): void
    {
        $item = $this->myRequests->firstWhere('id', $requestId);

        abort_unless($item && $item->created_by_id === Auth::id() && $item->status === BudgetLineItemStatus::NotStarted, 403);

        $item->delete();

        unset($this->myRequests);
        $this->editingRequestId = null;
        Flux::modal('request-new-circuit')->close();

        Flux::toast(variant: 'success', text: __('Circuit request deleted.'));
    }

    public function stopEditingRequest(): void
    {
        $this->editingRequestId = null;
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Metronet Circuit Review') }}</flux:heading>
    <flux:subheading>
        {{ __('Confirm whether each Metronet circuit in your area is still needed this budget cycle, or request a new one.') }}
    </flux:subheading>

    <div class="mt-6">
        @if (!$this->openCycle)
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.heading>{{ __('No open budget cycle') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Metronet circuits can only be reviewed while a budget cycle is open.') }}
                </flux:callout.text>
            </flux:callout>
        @else
            @if ($this->requestableDivisions->isNotEmpty())
                <div class="mb-6 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Request a new circuit') }}</flux:heading>
                    <flux:modal.trigger name="request-new-circuit">
                        <flux:button wire:click="openNewRequest" variant="primary" icon="plus">
                            {{ __('Request new circuit') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                @if ($this->myRequests->isNotEmpty())
                    <flux:table class="mb-8">
                        <flux:table.columns>
                            <flux:table.column>{{ __('Division') }}</flux:table.column>
                            <flux:table.column>{{ __('Location') }}</flux:table.column>
                            <flux:table.column>{{ __('GL code') }}</flux:table.column>
                            <flux:table.column>{{ __('Justification') }}</flux:table.column>
                            <flux:table.column>{{ __('Status') }}</flux:table.column>
                            <flux:table.column>{{ __('Requested by') }}</flux:table.column>
                            <flux:table.column sticky class="bg-white dark:bg-zinc-900"></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->myRequests as $request)
                                <flux:table.row :key="$request->id">
                                    <flux:table.cell>
                                        <flux:text size="sm">
                                            {{ $this->breadcrumb($request->responsibleDivision?->department_name, $request->responsibleDivision?->name) ?: '—' }}
                                        </flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:text size="sm">{{ $request->description }}</flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:text size="sm">
                                            {{ $request->glAllocations->first()?->glCode?->code_string ?? __('Pending Finance assignment') }}
                                        </flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:text size="sm" class="line-clamp-2">{{ $request->justification }}</flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge size="sm" :color="$request->status->getColor()">
                                            {{ $request->status->getLabel() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:text size="sm">{{ $request->createdBy?->name ?? '—' }}</flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell sticky class="bg-white dark:bg-zinc-900">
                                        @if ($request->created_by_id === Auth::id() && $request->status === \App\Enums\BudgetLineItemStatus::NotStarted)
                                            <flux:modal.trigger name="request-new-circuit">
                                                <flux:button wire:click="editRequest({{ $request->id }})" size="sm"
                                                    variant="primary" color="blue">
                                                    {{ __('Edit') }}
                                                </flux:button>
                                            </flux:modal.trigger>
                                        @else
                                            <flux:badge size="sm">{{ __('View only') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif

                <flux:modal name="request-new-circuit" class="max-w-lg" @close="stopEditingRequest">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Request a new circuit') }}</flux:heading>
                            <flux:subheading>
                                {{ __('For a Metronet circuit at a location that does not already have one.') }}
                            </flux:subheading>
                        </div>

                        <flux:select wire:model.live="newRequest.responsible_division_id" :label="__('Division')">
                            <flux:select.option value="" class="placeholder">
                                {{ __('Select a division') }}
                            </flux:select.option>
                            @foreach ($this->requestableDivisions as $division)
                                <flux:select.option value="{{ $division->id }}">
                                    {{ $this->breadcrumb($division->department_name, $division->name) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="newRequest.location" :label="__('Location')"
                            placeholder="{{ __('Where is this circuit needed?') }}" />

                        <flux:textarea wire:model="newRequest.justification" :label="__('Justification')"
                            placeholder="{{ __('Why is this needed?') }}" rows="3" />

                        <flux:text size="sm">
                            {{ __('GL code:') }}
                            @if (filled($this->newRequest['responsible_division_id'] ?? null))
                                {{ $this->resolvedGlCode($this->newRequest['responsible_division_id'])?->code_string ?? __('Pending Finance assignment') }}
                            @else
                                {{ __('Select a division to determine the GL code') }}
                            @endif
                        </flux:text>

                        <div class="flex items-center justify-between gap-2">
                            <div>
                                @if ($this->editingRequestId)
                                    <flux:button wire:click="deleteRequest({{ $this->editingRequestId }})"
                                        variant="ghost">
                                        {{ __('Delete request') }}
                                    </flux:button>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <flux:modal.close>
                                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button wire:click="saveRequest" variant="primary">
                                    {{ __('Save') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </flux:modal>
            @endif

            @if ($this->baseReviewableCircuits->isEmpty())
                <flux:callout icon="information-circle" variant="secondary">
                    <flux:callout.heading>{{ __('Nothing to review right now') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('No Metronet circuits in your area need review this budget cycle.') }}
                    </flux:callout.text>
                </flux:callout>
            @else
                <div class="mb-4 flex flex-wrap items-end gap-4">
                    <div class="min-w-64 flex-1">
                        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                            :placeholder="__('Search location, circuit number, or status...')" />
                    </div>
                    <flux:select wire:model.live="statusFilter" class="w-48" :label="__('Status')">
                        <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
                        <flux:select.option value="pending">{{ __('Pending review') }}</flux:select.option>
                        <flux:select.option value="keeping">{{ __('Still needed') }}</flux:select.option>
                        <flux:select.option value="discontinuing">{{ __('No longer needed') }}</flux:select.option>
                    </flux:select>
                    <flux:select wire:model.live="divisionFilter" class="w-56" :label="__('Division')">
                        <flux:select.option value="">{{ __('All divisions') }}</flux:select.option>
                        @foreach ($this->availableDivisions as $division)
                            <flux:select.option value="{{ $division['id'] }}">{{ $division['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @if ($this->search !== '' || $this->statusFilter !== 'all' || $this->divisionFilter !== '')
                        <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                            {{ __('Clear filters') }}
                        </flux:button>
                    @endif
                </div>

                @if ($this->reviewableCircuits->isEmpty())
                    <flux:callout icon="magnifying-glass" variant="secondary">
                        <flux:callout.heading>{{ __('No matches') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __('No circuits match your search or filters.') }}
                        </flux:callout.text>
                    </flux:callout>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Circuit') }}</flux:table.column>
                            <flux:table.column>{{ __('Circuit number') }}</flux:table.column>
                            <flux:table.column>{{ __('Status') }}</flux:table.column>
                            <flux:table.column>{{ __('Speed') }}</flux:table.column>
                            <flux:table.column>{{ __('Monthly cost') }}</flux:table.column>
                            <flux:table.column>{{ __('Yearly cost') }}</flux:table.column>
                            <flux:table.column>{{ __('Review') }}</flux:table.column>
                            <flux:table.column>{{ __('Justification') }}</flux:table.column>
                            <flux:table.column sticky class="bg-white dark:bg-zinc-900"></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->groupedRows as $row)
                                @continue(($row['hide_when_collapsed'] ?? false) && in_array($row['division_key'], $this->collapsedDivisions, true))

                                @if ($row['type'] === 'header' && $row['depth'] === 0)
                                    <flux:table.row>
                                        <flux:table.cell colspan="9"
                                            class="border-t border-zinc-200 bg-zinc-100 py-1 dark:border-zinc-700 dark:bg-zinc-800">
                                            <button type="button"
                                                wire:click="toggleDivision('{{ $row['division_key'] }}')"
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
                                        <flux:table.cell colspan="9"
                                            class="bg-zinc-50 pl-8 text-sm font-medium text-zinc-500 dark:bg-zinc-900/40 dark:text-zinc-400">
                                            {{ $row['label'] }}
                                        </flux:table.cell>
                                    </flux:table.row>
                                @elseif ($row['type'] === 'circuit')
                                    @php $circuit = $row['circuit']; @endphp
                                    <flux:table.row :key="$circuit->id">
                                        <flux:table.cell>
                                            <div class="font-medium">{{ $circuit->location_name ?? $circuit->tdx_asset_id }}</div>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm">{{ $circuit->circuit_number ?? '—' }}</flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm">{{ $circuit->status ?? '—' }}</flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm">{{ $circuit->speed ?? '—' }}</flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm">
                                                {{ $circuit->currentCost?->monthly_cost !== null ? '$' . number_format($circuit->currentCost->monthly_cost, 2) : '—' }}
                                            </flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm">
                                                {{ $circuit->currentCost?->yearly_cost !== null ? '$' . number_format($circuit->currentCost->yearly_cost, 2) : '—' }}
                                            </flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            @php $decision = $this->reviews[$circuit->id]['still_needed'] ?? ''; @endphp
                                            @if ($decision === '1')
                                                <flux:badge size="sm" color="green">{{ __('Still needed') }}</flux:badge>
                                            @elseif ($decision === '0')
                                                <flux:badge size="sm" color="red">{{ __('No longer needed') }}</flux:badge>
                                            @else
                                                <flux:badge size="sm" color="zinc">{{ __('Pending') }}</flux:badge>
                                            @endif
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm" class="line-clamp-2">
                                                {{ $this->reviews[$circuit->id]['justification'] ?? '—' }}
                                            </flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell sticky class="bg-white dark:bg-zinc-900">
                                            @if ($this->canEdit($circuit))
                                                <flux:modal.trigger name="edit-review">
                                                    <flux:button wire:click="edit({{ $circuit->id }})" size="sm"
                                                        variant="primary" color="blue">
                                                        {{ __('Review') }}
                                                    </flux:button>
                                                </flux:modal.trigger>
                                            @else
                                                <flux:badge size="sm">{{ __('View only') }}</flux:badge>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endif
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            @endif

            <flux:modal name="edit-review" class="max-w-lg" @close="stopEditing">
                @if ($circuit = $this->editingCircuit)
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ $circuit->location_name ?? $circuit->tdx_asset_id }}</flux:heading>
                            <flux:subheading>{{ $circuit->circuit_number }}</flux:subheading>
                        </div>

                        <flux:select wire:model.live="reviews.{{ $circuit->id }}.still_needed" :label="__('Is this circuit still needed?')">
                            <flux:select.option value="" class="placeholder">
                                {{ __('Not yet reviewed') }}
                            </flux:select.option>
                            <flux:select.option value="1">{{ __('Yes, still needed') }}</flux:select.option>
                            <flux:select.option value="0">{{ __('No, no longer needed') }}</flux:select.option>
                        </flux:select>

                        @if (($this->reviews[$circuit->id]['still_needed'] ?? '') === '1')
                            <flux:textarea wire:model="reviews.{{ $circuit->id }}.justification" :label="__('Justification')"
                                placeholder="{{ __('Why is this circuit still needed?') }}" rows="3" />
                        @endif

                        <div class="flex items-center justify-between gap-2">
                            <div>
                                @if ($circuit->reviews->isNotEmpty())
                                    <flux:button wire:click="clear({{ $circuit->id }})" variant="ghost">
                                        {{ __('Clear review') }}
                                    </flux:button>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <flux:modal.close>
                                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button wire:click="save({{ $circuit->id }})" variant="primary">
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
