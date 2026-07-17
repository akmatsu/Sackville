<?php

namespace App\Filament\Resources\Divisions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DivisionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_code')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->required()
                    ->live(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
