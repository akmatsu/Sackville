<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FundType: string implements HasLabel
{
    case AreaWide = 'areawide';
    case NonAreaWide = 'non_areawide';
    case ServiceArea = 'service_area';
    case Enterprise = 'enterprise';

    public function getLabel(): string
    {
        return match ($this) {
            self::AreaWide => 'Areawide',
            self::NonAreaWide => 'Non-Areawide',
            self::ServiceArea => 'Service Area',
            self::Enterprise => 'Enterprise',
        };
    }
}
