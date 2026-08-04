<?php

namespace App\Filament\Resources\TdxAssets\Tables;

use App\Models\TdxAsset;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TdxAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_tag')
                    ->label('Asset tag')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial')
                    ->searchable(),
                TextColumn::make('model.name')
                    ->label('Model')
                    ->sortable(),
                TextColumn::make('assigned_user_upn')
                    ->label('Assigned user')
                    ->searchable(),
                TextColumn::make('division.name')
                    ->label('Division')
                    ->sortable(),
                TextColumn::make('fy_replacement')
                    ->label('FY replacement')
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->label('Last synced')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_synced_at', 'desc')
            ->filters([
                SelectFilter::make('assigned_division_id')
                    ->label('Division')
                    ->relationship('division', 'name'),
                SelectFilter::make('fy_replacement')
                    ->label('FY replacement')
                    ->options(fn () => TdxAsset::query()
                        ->whereNotNull('fy_replacement')
                        ->distinct()
                        ->orderBy('fy_replacement')
                        ->pluck('fy_replacement', 'fy_replacement')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
