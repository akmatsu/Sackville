<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BudgetLineItemType: string implements HasLabel
{
    case HardwareReplacement = 'hardware_replacement';
    case HardwareAddition = 'hardware_addition';
    case Software = 'software';
    case Network = 'network';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::HardwareReplacement => 'Hardware replacement',
            self::HardwareAddition => 'Hardware addition',
            self::Software => 'Software',
            self::Network => 'Network',
            self::Other => 'Other',
        };
    }
}
