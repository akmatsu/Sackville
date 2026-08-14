<?php

use App\Enums\BudgetCycleStatus;
use App\Models\BudgetCycle;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * The cycle worth showing on the dashboard: the open cycle if there is
     * one, otherwise the soonest upcoming draft cycle, otherwise the most
     * recently closed cycle.
     */
    #[Computed]
    public function cycle(): ?BudgetCycle
    {
        return BudgetCycle::query()->where('status', BudgetCycleStatus::Open)->latest('fiscal_year')->first()
            ?? BudgetCycle::query()->where('status', BudgetCycleStatus::Draft)->orderBy('opens_at')->first()
            ?? BudgetCycle::query()->where('status', BudgetCycleStatus::Closed)->latest('fiscal_year')->first();
    }

    #[Computed]
    public function statusLabel(): string
    {
        return match ($this->cycle?->status) {
            BudgetCycleStatus::Draft => __('Coming soon'),
            BudgetCycleStatus::Open => __('Open'),
            BudgetCycleStatus::Closed => __('Closed'),
            null => __('None'),
        };
    }

    #[Computed]
    public function statusColor(): string
    {
        return match ($this->cycle?->status) {
            BudgetCycleStatus::Draft => 'amber',
            BudgetCycleStatus::Open => 'green',
            BudgetCycleStatus::Closed => 'zinc',
            null => 'zinc',
        };
    }

    /**
     * How far through its window the ring should be filled: empty for a
     * cycle that hasn't opened yet, full once it's closed, and the
     * elapsed share of opens_at..closes_at while it's open.
     */
    #[Computed]
    public function progressRatio(): float
    {
        $cycle = $this->cycle;

        return match ($cycle?->status) {
            BudgetCycleStatus::Open => $this->elapsedRatio($cycle),
            BudgetCycleStatus::Closed => 1.0,
            default => 0.0,
        };
    }

    protected function elapsedRatio(BudgetCycle $cycle): float
    {
        $today = today();
        $totalDays = max(1, $cycle->opens_at->diffInDays($cycle->closes_at));
        $elapsedDays = $cycle->opens_at->lte($today) ? min($totalDays, $cycle->opens_at->diffInDays($today)) : 0;

        return $elapsedDays / $totalDays;
    }

    #[Computed]
    public function daysRemaining(): ?int
    {
        $cycle = $this->cycle;
        $today = today();

        return match ($cycle?->status) {
            BudgetCycleStatus::Draft => $today->lte($cycle->opens_at) ? $today->diffInDays($cycle->opens_at) : 0,
            BudgetCycleStatus::Open => $today->lte($cycle->closes_at) ? $today->diffInDays($cycle->closes_at) : 0,
            default => null,
        };
    }

    #[Computed]
    public function daysLabel(): string
    {
        return match ($this->cycle?->status) {
            BudgetCycleStatus::Draft => __('until open'),
            BudgetCycleStatus::Open => __('days left'),
            default => '',
        };
    }

    #[Computed]
    public function ringTrackClass(): string
    {
        return match ($this->cycle?->status) {
            BudgetCycleStatus::Draft => 'stroke-amber-100 dark:stroke-amber-950',
            BudgetCycleStatus::Open => 'stroke-green-100 dark:stroke-green-950',
            BudgetCycleStatus::Closed => 'stroke-zinc-200 dark:stroke-zinc-800',
            null => 'stroke-zinc-100 dark:stroke-zinc-800',
        };
    }

    #[Computed]
    public function ringFillClass(): string
    {
        return match ($this->cycle?->status) {
            BudgetCycleStatus::Draft => 'stroke-amber-400 dark:stroke-amber-500',
            BudgetCycleStatus::Open => 'stroke-green-500 dark:stroke-green-400',
            BudgetCycleStatus::Closed => 'stroke-zinc-400 dark:stroke-zinc-500',
            null => 'stroke-zinc-300 dark:stroke-zinc-700',
        };
    }
};
?>

<div class="flex h-full flex-col justify-between rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
    <div class="flex items-start justify-between gap-2">
        <flux:heading size="sm">{{ __('Budget Cycle') }}</flux:heading>
        <flux:badge size="sm" :color="$this->statusColor">{{ $this->statusLabel }}</flux:badge>
    </div>

    @if ($cycle = $this->cycle)
        <div class="flex items-center gap-4">
            <x-dashboard.ring-meter :ratio="$this->progressRatio" :size="80" :thickness="9"
                :track-class="$this->ringTrackClass" :fill-class="$this->ringFillClass">
                @if ($cycle->status === BudgetCycleStatus::Closed)
                    <flux:icon.check class="size-6 text-zinc-400 dark:text-zinc-500" />
                @else
                    <span
                        class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->daysRemaining }}</span>
                    <span
                        class="text-[0.65rem] leading-tight text-zinc-500 dark:text-zinc-400">{{ $this->daysLabel }}</span>
                @endif
            </x-dashboard.ring-meter>
            <div>
                <div class="text-2xl font-semibold text-zinc-900 dark:text-white">FY{{ $cycle->fiscal_year }}</div>
                <flux:text size="sm" class="mt-1">
                    @if ($cycle->status === BudgetCycleStatus::Draft)
                        {{ __('Opens :date', ['date' => $cycle->opens_at->format('M j, Y')]) }}
                    @elseif ($cycle->status === BudgetCycleStatus::Open)
                        {{ __('Closes :date', ['date' => $cycle->closes_at->format('M j, Y')]) }}
                    @else
                        {{ __('Closed :date', ['date' => $cycle->closes_at->format('M j, Y')]) }}
                    @endif
                </flux:text>
            </div>
        </div>
    @else
        <flux:text>{{ __('No budget cycle has been configured.') }}</flux:text>
    @endif
</div>
