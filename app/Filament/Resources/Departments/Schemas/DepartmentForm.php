<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Models\Department;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Department $record): bool => $record !== null)
                    ->dehydrated()
                    ->helperText('Cannot be changed after creation — other tables reference this code.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
