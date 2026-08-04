<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResponsibilityRole: string implements HasLabel
{
    case View = 'view';
    case Edit = 'edit';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
