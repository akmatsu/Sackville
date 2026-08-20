<?php

namespace App\Filament\Resources\TdxPublicWifiCircuits;

use App\Filament\Resources\TdxPublicWifiCircuits\Pages\ListTdxPublicWifiCircuits;
use App\Filament\Resources\TdxPublicWifiCircuits\Pages\ViewTdxPublicWifiCircuit;
use App\Filament\Resources\TdxPublicWifiCircuits\RelationManagers\CostsRelationManager;
use App\Filament\Resources\TdxPublicWifiCircuits\Schemas\TdxPublicWifiCircuitInfolist;
use App\Filament\Resources\TdxPublicWifiCircuits\Tables\TdxPublicWifiCircuitsTable;
use App\Models\TdxPublicWifiCircuit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TdxPublicWifiCircuitResource extends Resource
{
    protected static ?string $model = TdxPublicWifiCircuit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWifi;

    protected static string|UnitEnum|null $navigationGroup = 'Integrations & Logs';

    protected static ?string $navigationLabel = 'Public Wifi Circuits';

    protected static ?string $modelLabel = 'public wifi circuit';

    protected static ?string $pluralModelLabel = 'public wifi circuits';

    protected static ?string $recordTitleAttribute = 'location_name';

    public static function infolist(Schema $schema): Schema
    {
        return TdxPublicWifiCircuitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TdxPublicWifiCircuitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTdxPublicWifiCircuits::route('/'),
            'view' => ViewTdxPublicWifiCircuit::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            CostsRelationManager::class,
        ];
    }
}
