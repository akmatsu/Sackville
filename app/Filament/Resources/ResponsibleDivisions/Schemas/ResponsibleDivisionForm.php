<?php

namespace App\Filament\Resources\ResponsibleDivisions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ResponsibleDivisionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('department_name')
                    ->label('Department')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The responsible department name as it appears in TDX, e.g. "Information Technology". Not tied to the chart-of-accounts department list.'),
                TextInput::make('name')
                    ->label('Division')
                    ->required()
                    ->maxLength(255),
                Toggle::make('active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
