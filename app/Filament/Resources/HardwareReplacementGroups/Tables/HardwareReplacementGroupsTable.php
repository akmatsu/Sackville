<?php

namespace App\Filament\Resources\HardwareReplacementGroups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HardwareReplacementGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(60),
                TextColumn::make('replaceable_categories_count')
                    ->label('Categories')
                    ->counts('replaceableCategories'),
                TextColumn::make('eligible_models_count')
                    ->label('Eligible models')
                    ->counts('eligibleModels'),
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
