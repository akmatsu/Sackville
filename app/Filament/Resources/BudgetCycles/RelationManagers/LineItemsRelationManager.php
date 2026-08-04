<?php

namespace App\Filament\Resources\BudgetCycles\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LineItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'lineItems';

    protected static ?string $title = 'Line items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('item_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('description')
                    ->limit(60),
                TextColumn::make('proposed_cost')
                    ->label('Proposed cost')
                    ->money('usd'),
                TextColumn::make('status')
                    ->badge(),
            ]);
    }
}
