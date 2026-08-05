<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SyncFrequency: string implements HasLabel
{
    case Daily = 'daily';
    case EveryNHours = 'every_n_hours';

    public function getLabel(): string
    {
        return match ($this) {
            self::Daily => 'Daily at a specific time',
            self::EveryNHours => 'Every N hours',
        };
    }
}
