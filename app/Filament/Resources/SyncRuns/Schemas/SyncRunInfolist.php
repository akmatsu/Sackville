<?php

namespace App\Filament\Resources\SyncRuns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class SyncRunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Run')->columns(3)->schema([
                    TextEntry::make('integration'),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('started_at')
                        ->label('Started')
                        ->dateTime(),
                    TextEntry::make('finished_at')
                        ->label('Finished')
                        ->dateTime(),
                    TextEntry::make('records_synced')
                        ->label('Records synced'),
                    TextEntry::make('records_failed')
                        ->label('Records failed'),
                ]),
                TextEntry::make('errors')
                    ->formatStateUsing(function (mixed $state): string {
                        $decoded = is_string($state) ? json_decode($state, true) : $state;

                        return $decoded ? (json_encode($decoded, JSON_PRETTY_PRINT) ?: 'None') : 'None';
                    })
                    ->columnSpanFull(),
            ]);
    }
}
