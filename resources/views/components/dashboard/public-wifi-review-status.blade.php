<?php

use App\Models\BudgetCycle;
use App\Models\TdxPublicWifiCircuit;
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

        return TdxPublicWifiCircuit::query()->visibleTo(Auth::user())->reviewable()->count();
    }

    #[Computed]
    public function reviewedCount(): int
    {
        $cycle = $this->openCycle;

        if (! $cycle) {
            return 0;
        }

        return TdxPublicWifiCircuit::query()
            ->visibleTo(Auth::user())
            ->reviewable()
            ->whereHas('reviews', fn ($query) => $query->where('budget_cycle_id', $cycle->id))
            ->count();
    }

    #[Computed]
    public function reviewedRatio(): float
    {
        return $this->eligibleCount > 0 ? $this->reviewedCount / $this->eligibleCount : 0.0;
    }
};
?>

<a href="{{ route('public-wifi.reviews') }}" wire:navigate
    class="group flex h-full flex-col justify-between rounded-xl border border-neutral-200 p-4 transition-colors hover:border-zinc-300 dark:border-neutral-700 dark:hover:border-zinc-600">
    <div class="flex items-start justify-between gap-2">
        <flux:heading size="sm">{{ __('Public Wifi Circuit Review') }}</flux:heading>
        <flux:icon.arrow-right
            class="size-4 shrink-0 text-zinc-400 transition-transform group-hover:translate-x-0.5 group-hover:text-zinc-600 dark:group-hover:text-zinc-300" />
    </div>

    @if (! $this->openCycle)
        <flux:text>{{ __('No open budget cycle.') }}</flux:text>
    @elseif ($this->eligibleCount === 0)
        <flux:text>{{ __('No public wifi circuits need review this cycle.') }}</flux:text>
    @else
        <div class="flex items-center gap-4">
            <x-dashboard.ring-meter :ratio="$this->reviewedRatio" :size="80" :thickness="9"
                track-class="stroke-blue-100 dark:stroke-blue-950" fill-class="stroke-blue-500 dark:stroke-blue-400">
                <span
                    class="text-lg font-semibold text-zinc-900 dark:text-white">{{ round($this->reviewedRatio * 100) }}%</span>
            </x-dashboard.ring-meter>
            <div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $this->reviewedCount }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('of :count reviewed', ['count' => $this->eligibleCount]) }}
                    </span>
                </div>
                <flux:text size="sm" class="mt-1">
                    {{ trans_choice('{0} No circuits remaining|{1} :count circuit remaining|[2,*] :count circuits remaining', $this->eligibleCount - $this->reviewedCount, ['count' => $this->eligibleCount - $this->reviewedCount]) }}
                </flux:text>
            </div>
        </div>
    @endif
</a>
