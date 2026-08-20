<?php

namespace App\Filament\Resources\SyncRuns;

use App\Filament\Resources\SyncRuns\Pages\ListSyncRuns;
use App\Filament\Resources\SyncRuns\Pages\ViewSyncRun;
use App\Filament\Resources\SyncRuns\Schemas\SyncRunInfolist;
use App\Filament\Resources\SyncRuns\Tables\SyncRunsTable;
use App\Models\SyncRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SyncRunResource extends Resource
{
    protected static ?string $model = SyncRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Activity & Logs';

    protected static ?string $navigationLabel = 'Sync Runs';

    protected static ?string $recordTitleAttribute = 'integration';

    public static function infolist(Schema $schema): Schema
    {
        return SyncRunInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SyncRunsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSyncRuns::route('/'),
            'view' => ViewSyncRun::route('/{record}'),
        ];
    }
}
