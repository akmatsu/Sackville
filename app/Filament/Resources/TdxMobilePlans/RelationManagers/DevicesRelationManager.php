<?php

namespace App\Filament\Resources\TdxMobilePlans\RelationManagers;

use App\Filament\Resources\TdxAssets\TdxAssetResource;
use App\Models\TdxAsset;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Devices are synced separately into tdx_assets by SyncTdxMobileDevices and
 * matched to their plan by serial (see TdxAsset::plan()) — this is a
 * read-only view of that match, not a manageable relationship, so there's
 * no create/edit/delete here. Rows link out to the device's own page in
 * TdxAssetResource rather than duplicating its details.
 */
class DevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'devices';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('asset_tag')
            ->columns([
                TextColumn::make('asset_tag')
                    ->label('Asset tag')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial')
                    ->searchable(),
                TextColumn::make('model.name')
                    ->label('Model'),
                TextColumn::make('product_type')
                    ->label('Type'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Surplus' => 'danger',
                        'Production' => 'success',
                        null => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('last_synced_at')
                    ->label('Last synced')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_synced_at', 'desc')
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (TdxAsset $record): string => TdxAssetResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
