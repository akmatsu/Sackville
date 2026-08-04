<?php

namespace App\Filament\Resources\HardwareModels\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CostsRelationManager extends RelationManager
{
    protected static string $relationship = 'costs';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fiscal_year')
                    ->label('Fiscal year')
                    ->numeric()
                    ->required(),
                TextInput::make('unit_cost')
                    ->label('Unit cost')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Toggle::make('with_docking')
                    ->label('With docking')
                    ->default(false)
                    ->required(),
                TextInput::make('docking_upcharge')
                    ->label('Docking upcharge')
                    ->numeric()
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fiscal_year')
            ->columns([
                TextColumn::make('fiscal_year')
                    ->label('FY')
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Unit cost')
                    ->money('usd')
                    ->sortable(),
                IconColumn::make('with_docking')
                    ->label('With docking')
                    ->boolean(),
                TextColumn::make('docking_upcharge')
                    ->label('Docking upcharge')
                    ->money('usd'),
            ])
            ->defaultSort('fiscal_year', 'desc')
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
