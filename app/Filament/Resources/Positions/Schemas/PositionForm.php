<?php

namespace App\Filament\Resources\Positions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('department_code')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('division_id', null)),
                Select::make('division_id')
                    ->label('Division')
                    ->relationship(
                        name: 'division',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query->where('department_code', $get('department_code')),
                    )
                    ->required()
                    ->disabled(fn (Get $get): bool => blank($get('department_code'))),
            ]);
    }
}
