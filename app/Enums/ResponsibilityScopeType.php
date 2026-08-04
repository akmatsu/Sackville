<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResponsibilityScopeType: string implements HasLabel
{
    case Fund = 'fund';
    case Department = 'department';
    case Division = 'division';
    case Object = 'object';
    case SpecificGl = 'specific_gl';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fund => 'Fund',
            self::Department => 'Department',
            self::Division => 'Division',
            self::Object => 'Object code',
            self::SpecificGl => 'Specific GL code',
        };
    }
}
