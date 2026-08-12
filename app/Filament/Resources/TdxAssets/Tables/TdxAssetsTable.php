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
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Surplus' => 'danger',
                        'Production' => 'success',
                        null => 'gray',
                        default => 'warning',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assigned_user_upn')
                    ->label('Assigned user')
                    ->searchable(),
                TextColumn::make('responsibleDivision.name')
                    ->label('Responsible division')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('responsibleLocation.name')
                    ->label('Location')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('glCode.code_string')
                    ->label('GL code')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('fy_replacement')
                    ->label('FY replacement')
                    ->sortable(),
                TextColumn::make('warranty_ends_at')
                    ->label('Warranty ends')
                    ->date()
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->label('Last synced')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_synced_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => TdxAsset::query()
                        ->whereNotNull('status')
                        ->distinct()
                        ->orderBy('status')
                        ->pluck('status', 'status')
                        ->all()),
                SelectFilter::make('responsible_division_id')
                    ->label('Responsible division')
                    ->relationship('responsibleDivision', 'name'),
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
