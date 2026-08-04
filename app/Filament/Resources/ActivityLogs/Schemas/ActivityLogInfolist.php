<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Event')->columns(3)->schema([
                    TextEntry::make('table_name')
                        ->label('Table'),
                    TextEntry::make('record_id')
                        ->label('Record ID'),
                    TextEntry::make('action')
                        ->badge(),
                    TextEntry::make('actor.name')
                        ->label('Actor'),
                    TextEntry::make('at')
                        ->dateTime(),
                ]),
                TextEntry::make('diff')
                    ->formatStateUsing(function (mixed $state): string {
                        $decoded = is_string($state) ? json_decode($state, true) : $state;

                        return $decoded ? json_encode($decoded, JSON_PRETTY_PRINT) : 'None';
                    })
                    ->columnSpanFull(),
            ]);
    }
}
