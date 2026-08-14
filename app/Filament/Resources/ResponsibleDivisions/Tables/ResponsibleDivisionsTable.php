<?php

namespace App\Filament\Resources\ResponsibleDivisions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ResponsibleDivisionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department_name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Division')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locations_count')
                    ->label('Locations')
                    ->counts('locations')
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
