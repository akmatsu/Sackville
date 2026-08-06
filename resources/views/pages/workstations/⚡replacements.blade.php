<?php

use App\Enums\ResponsibilityRole;
use App\Models\BudgetCycle;
use App\Models\HardwareCategory;
use App\Models\HardwareModelCost;
use App\Models\HardwareReplacementGroup;
use App\Models\HardwareReplacementSelection;
use App\Models\Responsibility;
use App\Models\TdxAsset;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Workstation Replacements')] class extends Component {
    /**
     * @var array<int, array{hardware_model_id: int|null, with_docking: bool, notes: string|null}>
     */
    public array $selections = [];

    public function mount(): void
    {
        foreach ($this->eligibleAssets as $asset) {
            $existing = $asset->replacementSelections->first();

            $this->selections[$asset->id] = [
                'hardware_model_id' => $existing?->hardware_model_id,
                'with_docking' => $existing?->with_docking ?? false,
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
     * @return Collection<int, TdxAsset>
     */
    #[Computed]
    public function eligibleAssets(): Collection
    {
        $cycle = $this->openCycle;

        if (! $cycle) {
            return collect();
        }

        return TdxAsset::query()
            ->visibleTo(Auth::user())
            ->whereHas('model.category', fn ($query) => $query->where('name', 'Workstation'))
            ->where('fy_replacement', $cycle->fiscal_year)
            ->with([
                'model.category',
                'division',
                'glCode',
                'replacementSelections' => fn ($query) => $query->where('budget_cycle_id', $cycle->id),
            ])
            ->orderBy('assigned_location_name')
            ->get();
    }

    /**
     * Active replacement models eligible under the Workstation replacement group(s).
     *
     * @return Collection<int, \App\Models\HardwareModel>
     */
    #[Computed]
    public function eligibleModels(): Collection
    {
        $category = HardwareCategory::query()
            ->where('name', 'Workstation')
            ->with('hardwareReplacementGroups.eligibleModels')
            ->first();

        if (! $category) {
            return collect();
        }

        return $category->hardwareReplacementGroups
            ->filter(fn (HardwareReplacementGroup $group): bool => $group->active)
            ->flatMap(fn (HardwareReplacementGroup $group) => $group->eligibleModels)
            ->filter(fn ($model): bool => $model->active)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * Reference-only catalog unit costs for the open cycle's fiscal year, keyed by "{modelId}:{withDocking}".
     *
     * @return Collection<string, HardwareModelCost>
     */
    #[Computed]
    public function modelCosts(): Collection
    {
        $cycle = $this->openCycle;

        if (! $cycle) {
            return collect();
        }

        return HardwareModelCost::query()
            ->whereIn('hardware_model_id', $this->eligibleModels->pluck('id'))
            ->where('fiscal_year', $cycle->fiscal_year)
            ->get()
            ->keyBy(fn (HardwareModelCost $cost): string => $cost->hardware_model_id.':'.($cost->with_docking ? '1' : '0'));
    }

    public function canEdit(TdxAsset $asset): bool
    {
        return Auth::user()->responsibilities
            ->filter(fn (Responsibility $responsibility): bool => $responsibility->matchesAsset($asset))
            ->contains(fn (Responsibility $responsibility): bool => in_array(
                $responsibility->role,
                [ResponsibilityRole::Edit, ResponsibilityRole::Admin],
                true
            ));
    }

    public function save(int $tdxAssetId): void
    {
        $asset = $this->eligibleAssets->firstWhere('id', $tdxAssetId);
        $cycle = $this->openCycle;

        abort_unless($asset && $cycle && $this->canEdit($asset), 403);

        $validated = validator($this->selections[$tdxAssetId] ?? [], [
            'hardware_model_id' => ['required', Rule::in($this->eligibleModels->pluck('id'))],
            'with_docking' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $model = $this->eligibleModels->firstWhere('id', $validated['hardware_model_id']);

        HardwareReplacementSelection::updateOrCreate(
            ['budget_cycle_id' => $cycle->id, 'tdx_asset_id' => $asset->id],
            [
                'hardware_model_id' => $model->id,
                'with_docking' => $model->has_docking_option && ($validated['with_docking'] ?? false),
                'notes' => $validated['notes'] ?? null,
                'selected_by_id' => Auth::id(),
            ]
        );

        unset($this->eligibleAssets);

        Flux::toast(variant: 'success', text: __('Replacement selection saved.'));
    }

    public function clear(int $tdxAssetId): void
    {
        $asset = $this->eligibleAssets->firstWhere('id', $tdxAssetId);
        $cycle = $this->openCycle;

        abort_unless($asset && $cycle && $this->canEdit($asset), 403);

        HardwareReplacementSelection::query()
            ->where('budget_cycle_id', $cycle->id)
            ->where('tdx_asset_id', $asset->id)
            ->delete();

        $this->selections[$tdxAssetId] = [
            'hardware_model_id' => null,
            'with_docking' => false,
            'notes' => null,
        ];

        unset($this->eligibleAssets);

        Flux::toast(variant: 'success', text: __('Selection cleared.'));
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Workstation Replacements') }}</flux:heading>
    <flux:subheading>
        {{ __('Select a replacement model for workstations due for replacement in your area this budget cycle.') }}
    </flux:subheading>

    <div class="mt-6">
        @if (! $this->openCycle)
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.heading>{{ __('No open budget cycle') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Workstation replacements can only be selected while a budget cycle is open.') }}
                </flux:callout.text>
            </flux:callout>
        @elseif ($this->eligibleAssets->isEmpty())
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.heading>{{ __('Nothing to replace right now') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('No workstations in your area are flagged for replacement in this budget cycle.') }}
                </flux:callout.text>
            </flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Asset') }}</flux:table.column>
                    <flux:table.column>{{ __('Assigned to') }}</flux:table.column>
                    <flux:table.column>{{ __('Warranty ends') }}</flux:table.column>
                    <flux:table.column>{{ __('Replacement model') }}</flux:table.column>
                    <flux:table.column>{{ __('Docking station') }}</flux:table.column>
                    <flux:table.column>{{ __('Notes') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->eligibleAssets as $asset)
                        <flux:table.row :key="$asset->id">
                            <flux:table.cell>
                                <div class="font-medium">{{ $asset->asset_tag ?? $asset->tdx_asset_id }}</div>
                                <flux:text size="sm">{{ $asset->model?->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text size="sm">{{ $asset->assigned_location_name ?? $asset->division?->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text size="sm">{{ $asset->warranty_ends_at?->format('M j, Y') ?? '—' }}</flux:text>
                            </flux:table.cell>

                            @if ($this->canEdit($asset))
                                <flux:table.cell>
                                    <flux:select wire:model="selections.{{ $asset->id }}.hardware_model_id" placeholder="{{ __('Choose a model...') }}" size="sm">
                                        @foreach ($this->eligibleModels as $model)
                                            <flux:select.option value="{{ $model->id }}">
                                                {{ $model->name }}
                                                @if ($cost = $this->modelCosts->get($model->id.':0'))
                                                    — ${{ number_format($cost->unit_cost, 2) }}
                                                @endif
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:checkbox wire:model="selections.{{ $asset->id }}.with_docking" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:input wire:model="selections.{{ $asset->id }}.notes" size="sm" placeholder="{{ __('Optional') }}" />
                                </flux:table.cell>
                                <flux:table.cell class="flex gap-2">
                                    <flux:button wire:click="save({{ $asset->id }})" size="sm" variant="primary">
                                        {{ __('Save') }}
                                    </flux:button>
                                    @if ($asset->replacementSelections->isNotEmpty())
                                        <flux:button wire:click="clear({{ $asset->id }})" size="sm" variant="ghost">
                                            {{ __('Clear') }}
                                        </flux:button>
                                    @endif
                                </flux:table.cell>
                            @else
                                <flux:table.cell colspan="4">
                                    <flux:text size="sm">
                                        @if ($existing = $asset->replacementSelections->first())
                                            {{ __('Selected: ') }}{{ $existing->hardwareModel?->name }}
                                        @else
                                            {{ __('View only — you cannot select a replacement for this asset.') }}
                                        @endif
                                    </flux:text>
                                </flux:table.cell>
                            @endif
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</section>
