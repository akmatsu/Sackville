<?php

namespace App\Filament\Resources\SubObjectCodes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SubObjectCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('object_code')
                    ->label('Object code')
                    ->relationship('objectCode', 'name')
                    ->required()
                    ->live(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule, Get $get) => $rule->where('object_code', $get('object_code')),
                    ),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
