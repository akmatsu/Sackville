<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NetworkRequestSource: string implements HasLabel
{
    case PublicWifi = 'public_wifi';
    case Metronet = 'metronet';

    public function getLabel(): string
    {
        return match ($this) {
            self::PublicWifi => 'Public wifi',
            self::Metronet => 'Metronet',
        };
    }
}
