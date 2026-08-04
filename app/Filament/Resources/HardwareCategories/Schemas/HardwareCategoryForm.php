<?php

namespace App\Filament\Resources\HardwareCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HardwareCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }
}
