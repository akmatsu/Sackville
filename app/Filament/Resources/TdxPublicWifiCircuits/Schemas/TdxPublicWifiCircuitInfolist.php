<?php

namespace App\Filament\Resources\TdxPublicWifiCircuits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class TdxPublicWifiCircuitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('TDX')->columns(3)->schema([
                    TextEntry::make('tdx_asset_id')
                        ->label('TDX asset ID'),
                    TextEntry::make('location_name')
                        ->label('Location'),
                    TextEntry::make('address')
                        ->placeholder('—'),
                    TextEntry::make('speed')
                        ->placeholder('—'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'Surplus' => 'danger',
                            'Active' => 'success',
                            null => 'gray',
                            default => 'warning',
                        }),
                    TextEntry::make('po_number')
                        ->label('PO number')
                        ->placeholder('—'),
                    TextEntry::make('monthly_cost')
                        ->label('Monthly cost')
                        ->money('USD'),
                    TextEntry::make('yearly_cost')
                        ->label('Yearly cost')
                        ->money('USD'),
                    TextEntry::make('purchase_cost')
                        ->label('Purchase cost')
                        ->money('USD'),
                    TextEntry::make('last_synced_at')
                        ->label('Last synced')
                        ->dateTime(),
                ]),
                TextEntry::make('notes')
                    ->placeholder('—')
                    ->columnSpanFull(),
                Fieldset::make('Funding')->columns(3)->schema([
                    TextEntry::make('assigned_department_code')
                        ->label('Responsible department')
                        ->placeholder('—'),
                    TextEntry::make('responsibleDivision.name')
                        ->label('Responsible division')
                        ->placeholder('—'),
                    TextEntry::make('responsibleLocation.name')
                        ->label('Responsible location')
                        ->placeholder('—'),
                    TextEntry::make('glCode.code_string')
                        ->label('GL code')
                        ->placeholder('—')
                        ->helperText('What this is coded to in the budget — independent of the responsible department/division above, since IT funds services for every department.'),
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
