<?php

namespace App\Filament\Resources\HardwareCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                Select::make('default_object_code')
                    ->label('Default object code')
                    ->relationship('defaultObjectCode', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('default_sub_object_code_id', null))
                    ->helperText('GL segment 4 used for new-asset requests in this category.'),
                Select::make('default_sub_object_code_id')
                    ->label('Default sub-object code')
                    ->relationship(
                        name: 'defaultSubObjectCode',
                        titleAttribute: 'code',
                        modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query->where('object_code', $get('default_object_code')),
                    )
                    ->searchable()
                    ->preload()
                    ->disabled(fn (Get $get): bool => blank($get('default_object_code')))
                    ->helperText('GL segment 5 used for new-asset requests in this category.'),
            ]);
    }
}
