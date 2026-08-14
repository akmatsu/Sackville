<?php

namespace App\Filament\Resources\TdxMobilePlans\Tables;

use App\Models\TdxMobilePlan;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TdxMobilePlansTable
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
                TextColumn::make('carrier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('po_number')
                    ->label('PO number')
                    ->searchable(),
                TextColumn::make('plan_status')
                    ->label('Plan status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Active' => 'success',
                        null => 'gray',
                        default => 'warning',
                    })
                    ->searchable()
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
                TextColumn::make('last_synced_at')
                    ->label('Last synced')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_synced_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => TdxMobilePlan::query()
                        ->whereNotNull('status')
                        ->distinct()
                        ->orderBy('status')
                        ->pluck('status', 'status')
                        ->all()),
                SelectFilter::make('plan_status')
                    ->label('Plan status')
                    ->options(fn () => TdxMobilePlan::query()
                        ->whereNotNull('plan_status')
                        ->distinct()
                        ->orderBy('plan_status')
                        ->pluck('plan_status', 'plan_status')
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
