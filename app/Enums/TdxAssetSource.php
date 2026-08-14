<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TdxAssetSource: string implements HasColor, HasLabel
{
    case Workstation = 'workstation';
    case Mobile = 'mobile';

    public function getLabel(): string
    {
        return match ($this) {
            self::Workstation => 'Workstation',
            self::Mobile => 'Mobile',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Workstation => 'gray',
            self::Mobile => 'info',
        };
    }
}
