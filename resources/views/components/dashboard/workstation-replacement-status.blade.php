<?php

use App\Models\BudgetCycle;
use App\Models\TdxAsset;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function openCycle(): ?BudgetCycle
    {
        return BudgetCycle::query()->open()->latest('fiscal_year')->first();
    }

    #[Computed]
    public function eligibleCount(): int
    {
        $cycle = $this->openCycle;

        if (! $cycle) {
            return 0;
        }

        return TdxAsset::query()->visibleTo(Auth::user())->eligibleForReplacement('Workstation', $cycle)->count();
    }

    #[Computed]
    public function selectedCount(): int
    {
        $cycle = $this->openCycle;

        if (! $cycle) {
            return 0;
        }

        return TdxAsset::query()
            ->visibleTo(Auth::user())
            ->eligibleForReplacement('Workstation', $cycle)
            ->whereHas('replacementSelections', fn ($query) => $query->where('budget_cycle_id', $cycle->id)->whereNotNull('hardware_model_id'))
            ->count();
    }

    #[Computed]
    public function selectedRatio(): float
    {
        return $this->eligibleCount > 0 ? $this->selectedCount / $this->eligibleCount : 0.0;
    }
};
?>

<a href="{{ route('workstations.replacements') }}" wire:navigate
    class="group flex h-full flex-col justify-between rounded-xl border border-neutral-200 p-4 transition-colors hover:border-zinc-300 dark:border-neutral-700 dark:hover:border-zinc-600">
    <div class="flex items-start justify-between gap-2">
        <flux:heading size="sm">{{ __('Workstation Replacements') }}</flux:heading>
        <flux:icon.arrow-right
            class="size-4 shrink-0 text-zinc-400 transition-transform group-hover:translate-x-0.5 group-hover:text-zinc-600 dark:group-hover:text-zinc-300" />
    </div>

    @if (! $this->openCycle)
        <flux:text>{{ __('No open budget cycle.') }}</flux:text>
    @elseif ($this->eligibleCount === 0)
        <flux:text>{{ __('No workstations are eligible for replacement this cycle.') }}</flux:text>
    @else
        <div class="flex items-center gap-4">
            <x-dashboard.ring-meter :ratio="$this->selectedRatio" :size="80" :thickness="9"
                track-class="stroke-blue-100 dark:stroke-blue-950" fill-class="stroke-blue-500 dark:stroke-blue-400">
                <span
                    class="text-lg font-semibold text-zinc-900 dark:text-white">{{ round($this->selectedRatio * 100) }}%</span>
            </x-dashboard.ring-meter>
            <div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $this->selectedCount }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('of :count selected', ['count' => $this->eligibleCount]) }}
                    </span>
                </div>
                <flux:text size="sm" class="mt-1">
                    {{ trans_choice('{0} No workstations remaining|{1} :count workstation remaining|[2,*] :count workstations remaining', $this->eligibleCount - $this->selectedCount, ['count' => $this->eligibleCount - $this->selectedCount]) }}
                </flux:text>
            </div>
        </div>
    @endif
</a>
