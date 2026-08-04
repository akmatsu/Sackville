<?php

namespace App\Filament\Resources\TdxAssets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class TdxAssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('TDX')->columns(3)->schema([
                    TextEntry::make('tdx_asset_id')
                        ->label('TDX asset ID'),
                    TextEntry::make('asset_tag')
                        ->label('Asset tag'),
                    TextEntry::make('serial'),
                    TextEntry::make('model.name')
                        ->label('Model'),
                    TextEntry::make('assigned_user_upn')
                        ->label('Assigned user'),
                    TextEntry::make('assigned_department_code')
                        ->label('Department code'),
                    TextEntry::make('division.name')
                        ->label('Division'),
                    TextEntry::make('acquired_at')
                        ->label('Acquired')
                        ->date(),
                    TextEntry::make('fy_replacement')
                        ->label('FY replacement'),
                    TextEntry::make('last_synced_at')
                        ->label('Last synced')
                        ->dateTime(),
                ]),
                TextEntry::make('raw_payload')
                    ->label('Raw TDX payload')
                    ->formatStateUsing(function (mixed $state): string {
                        $decoded = is_string($state) ? json_decode($state, true) : $state;

                        return $decoded ? json_encode($decoded, JSON_PRETTY_PRINT) : '';
                    })
                    ->columnSpanFull(),
            ]);
    }
}
