<?php

namespace App\Filament\Resources\SoftwareProducts\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LicensesRelationManager extends RelationManager
{
    protected static string $relationship = 'licenses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fiscal_year')
                    ->label('Fiscal year')
                    ->numeric()
                    ->required(),
                TextInput::make('license_count')
                    ->label('License count')
                    ->numeric()
                    ->default(1)
                    ->required(),
                TextInput::make('unit_cost')
                    ->label('Unit cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('total_cost')
                    ->label('Total cost')
                    ->numeric()
                    ->prefix('$'),
                DatePicker::make('license_expiration')
                    ->label('License expiration'),
                Textarea::make('license_notes')
                    ->label('License notes')
                    ->columnSpanFull(),
                Textarea::make('justification')
                    ->columnSpanFull(),
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
                TextColumn::make('license_count')
                    ->label('Count')
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Unit cost')
                    ->money('usd'),
                TextColumn::make('total_cost')
                    ->label('Total cost')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('license_expiration')
                    ->label('Expires')
                    ->date(),
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
