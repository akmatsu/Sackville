<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\ResponsibilityRole;
use App\Enums\ResponsibilityScopeType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsibilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'responsibilities';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('scope_type')
                    ->label('Scope type')
                    ->options(ResponsibilityScopeType::class)
                    ->required()
                    ->live(),
                TextInput::make('scope_value')
                    ->label('Scope value')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The fund, department, division, object, or GL code this scope applies to, matching the selected scope type.'),
                Select::make('role')
                    ->options(ResponsibilityRole::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('scope_value')
            ->columns([
                TextColumn::make('scope_type')
                    ->label('Scope type')
                    ->badge(),
                TextColumn::make('scope_value')
                    ->label('Scope value')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
