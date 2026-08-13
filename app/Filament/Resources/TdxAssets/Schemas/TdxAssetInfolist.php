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
                    TextEntry::make('source')
                        ->badge(),
                    TextEntry::make('asset_tag')
                        ->label('Asset tag'),
                    TextEntry::make('serial'),
                    TextEntry::make('product_type')
                        ->label('Type')
                        ->placeholder('—'),
                    TextEntry::make('model.name')
                        ->label('Model'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'Surplus' => 'danger',
                            'Production' => 'success',
                            null => 'gray',
                            default => 'warning',
                        }),
                    TextEntry::make('description'),
                    TextEntry::make('assigned_user_upn')
                        ->label('Assigned user'),
                    TextEntry::make('acquired_at')
                        ->label('Acquired')
                        ->date(),
                    TextEntry::make('fy_replacement')
                        ->label('FY replacement'),
                    TextEntry::make('warranty_ends_at')
                        ->label('Warranty ends')
                        ->date(),
                    TextEntry::make('last_synced_at')
                        ->label('Last synced')
                        ->dateTime(),
                ]),
                Fieldset::make('Funding')->columns(3)->schema([
                    TextEntry::make('assigned_department_code')
                        ->label('Responsible department')
                        ->placeholder('—'),
                    TextEntry::make('responsibleDivision.name')
                        ->label('Responsible division')
                        ->placeholder('—'),
                    TextEntry::make('responsibleLocation.name')
                        ->label('Location')
                        ->placeholder('—'),
                    TextEntry::make('glCode.code_string')
                        ->label('GL code')
                        ->placeholder('—')
                        ->helperText('What this is coded to in the budget — independent of the responsible department/division above, since IT funds hardware for every department.'),
                ]),
                Fieldset::make('Plan')->columns(4)->schema([
                    TextEntry::make('plan.carrier')
                        ->label('Carrier')
                        ->placeholder('—'),
                    TextEntry::make('plan.serial')
                        ->label('Plan line')
                        ->placeholder('—'),
                    TextEntry::make('plan.plan_status')
                        ->label('Plan status')
                        ->placeholder('—'),
                    TextEntry::make('plan.po_number')
                        ->label('PO number')
                        ->placeholder('—'),
                ]),
                TextEntry::make('raw_payload')
                    ->label('Raw TDX payload')
                    ->formatStateUsing(function (mixed $state): string {
                        $decoded = is_string($state) ? json_decode($state, true) : $state;

                        return $decoded ? (json_encode($decoded, JSON_PRETTY_PRINT) ?: '') : '';
                    })
                    ->columnSpanFull(),
            ]);
    }
}
