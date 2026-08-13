<?php

namespace App\Filament\Resources\TdxMobilePlans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class TdxMobilePlanInfolist
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
                    TextEntry::make('carrier'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'Surplus' => 'danger',
                            'Production' => 'success',
                            null => 'gray',
                            default => 'warning',
                        }),
                    TextEntry::make('plan_status')
                        ->label('Plan status')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'Active' => 'success',
                            null => 'gray',
                            default => 'warning',
                        }),
                    TextEntry::make('po_number')
                        ->label('PO number'),
                    TextEntry::make('description'),
                    TextEntry::make('assigned_user_upn')
                        ->label('Assigned user'),
                    TextEntry::make('last_synced_at')
                        ->label('Last synced')
                        ->dateTime(),
                ]),
                TextEntry::make('plan_description')
                    ->label('Plan')
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
                        ->label('Location')
                        ->placeholder('—'),
                    TextEntry::make('glCode.code_string')
                        ->label('GL code')
                        ->placeholder('—')
                        ->helperText('What this is coded to in the budget — independent of the responsible department/division above, since IT funds hardware for every department.'),
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
