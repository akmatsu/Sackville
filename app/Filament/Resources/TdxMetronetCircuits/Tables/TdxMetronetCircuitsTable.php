<?php

namespace App\Filament\Resources\TdxMetronetCircuits\Tables;

use App\Models\TdxMetronetCircuit;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TdxMetronetCircuitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location_name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('circuit_number')
                    ->label('Circuit number')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Surplus' => 'danger',
                        'Active' => 'success',
                        null => 'gray',
                        default => 'warning',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('speed')
                    ->placeholder('—'),
                TextColumn::make('currentCost.monthly_cost')
                    ->label('Monthly cost')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('currentCost.yearly_cost')
                    ->label('Yearly cost')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('responsibleDivision.name')
                    ->label('Responsible division')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('responsibleLocation.name')
                    ->label('Responsible location')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('glCode.code_string')
                    ->label('GL code')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->label('Last synced')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_synced_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => TdxMetronetCircuit::query()
                        ->whereNotNull('status')
                        ->distinct()
                        ->orderBy('status')
                        ->pluck('status', 'status')
                        ->all()),
                SelectFilter::make('responsible_division_id')
                    ->label('Responsible division')
                    ->relationship('responsibleDivision', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
