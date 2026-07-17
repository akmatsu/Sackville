<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ObjectCodeCategory: string implements HasLabel
{
    case Wages = 'wages';
    case Travel = 'travel';
    case Supplies = 'supplies';
    case Equipment = 'equipment';
    case Software = 'software';
    case Contractual = 'contractual';
    case Other = 'other';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
