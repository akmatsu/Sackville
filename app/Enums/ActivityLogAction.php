<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ActivityLogAction: string implements HasColor, HasLabel
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Create => 'success',
            self::Update => 'warning',
            self::Delete => 'danger',
        };
    }
}
