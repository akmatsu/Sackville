<?php

namespace App\Filament\Resources\GlCodes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GlCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['fund', 'department', 'division', 'objectCode', 'subObjectCode']))
            ->columns([
                TextColumn::make('code_string')
                    ->label('GL code')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fund.code')
                    ->label('Fund'),
                TextColumn::make('department.code')
                    ->label('Department'),
                TextColumn::make('division.code')
                    ->label('Division')
                    ->placeholder('—'),
                TextColumn::make('objectCode.code')
                    ->label('Object')
                    ->placeholder('—'),
                TextColumn::make('subObjectCode.code')
                    ->label('Sub-object')
                    ->placeholder('—'),
                IconColumn::make('active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('fund_code')
                    ->label('Fund')
                    ->relationship('fund', 'name'),
                SelectFilter::make('department_code')
                    ->label('Department')
                    ->relationship('department', 'name'),
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
