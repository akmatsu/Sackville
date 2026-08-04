<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Enums\ActivityLogAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('table_name')
                    ->label('Table')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('record_id')
                    ->label('Record ID')
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->options(ActivityLogAction::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
