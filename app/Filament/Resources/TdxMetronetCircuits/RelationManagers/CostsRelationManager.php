<?php

namespace App\Filament\Resources\TdxMetronetCircuits\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Cost history is written by SyncTdxMetronet (one row per fiscal year) —
 * this is a read-only view of that history, not a manageable relationship,
 * so there's no create/edit/delete here.
 */
class CostsRelationManager extends RelationManager
{
    protected static string $relationship = 'costs';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fiscal_year')
            ->columns([
                TextColumn::make('fiscal_year')
                    ->label('FY')
                    ->sortable(),
                TextColumn::make('monthly_cost')
                    ->label('Monthly cost')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('yearly_cost')
                    ->label('Yearly cost')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('purchase_cost')
                    ->label('Purchase cost')
                    ->money('USD'),
            ])
            ->defaultSort('fiscal_year', 'desc');
    }
}
