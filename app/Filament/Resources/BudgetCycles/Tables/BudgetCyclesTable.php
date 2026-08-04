<?php

namespace App\Filament\Resources\BudgetCycles\Tables;

use App\Enums\BudgetCycleStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BudgetCyclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fiscal_year')
                    ->label('FY')
                    ->sortable(),
                TextColumn::make('opens_at')
                    ->label('Opens')
                    ->date()
                    ->sortable(),
                TextColumn::make('closes_at')
                    ->label('Closes')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('line_items_count')
                    ->label('Line items')
                    ->counts('lineItems'),
            ])
            ->defaultSort('fiscal_year', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(BudgetCycleStatus::class),
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
