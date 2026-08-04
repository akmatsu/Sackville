<?php

namespace App\Filament\Resources\BudgetLineItems\Tables;

use App\Enums\BudgetLineItemStatus;
use App\Enums\BudgetLineItemType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BudgetLineItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cycle.fiscal_year')
                    ->label('FY')
                    ->sortable(),
                TextColumn::make('item_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('proposed_cost')
                    ->label('Proposed cost')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('Created by')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('budget_cycle_id')
                    ->label('Budget cycle')
                    ->relationship('cycle', 'fiscal_year'),
                SelectFilter::make('item_type')
                    ->options(BudgetLineItemType::class),
                SelectFilter::make('status')
                    ->options(BudgetLineItemStatus::class),
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
