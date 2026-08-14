<?php

namespace App\Filament\Resources\SyncRuns\Tables;

use App\Enums\SyncRunStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SyncRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('integration')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Finished')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('records_synced')
                    ->label('Synced')
                    ->sortable(),
                TextColumn::make('records_failed')
                    ->label('Failed')
                    ->sortable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->poll('3s')
            ->filters([
                SelectFilter::make('status')
                    ->options(SyncRunStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
