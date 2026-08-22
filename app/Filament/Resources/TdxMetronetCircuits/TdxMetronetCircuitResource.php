<?php

namespace App\Filament\Resources\TdxMetronetCircuits;

use App\Filament\Resources\TdxMetronetCircuits\Pages\ListTdxMetronetCircuits;
use App\Filament\Resources\TdxMetronetCircuits\Pages\ViewTdxMetronetCircuit;
use App\Filament\Resources\TdxMetronetCircuits\RelationManagers\CostsRelationManager;
use App\Filament\Resources\TdxMetronetCircuits\Schemas\TdxMetronetCircuitInfolist;
use App\Filament\Resources\TdxMetronetCircuits\Tables\TdxMetronetCircuitsTable;
use App\Models\TdxMetronetCircuit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TdxMetronetCircuitResource extends Resource
{
    protected static ?string $model = TdxMetronetCircuit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Metronet Circuits';

    protected static ?string $modelLabel = 'Metronet circuit';

    protected static ?string $pluralModelLabel = 'Metronet circuits';

    protected static ?string $recordTitleAttribute = 'location_name';

    public static function infolist(Schema $schema): Schema
    {
        return TdxMetronetCircuitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TdxMetronetCircuitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTdxMetronetCircuits::route('/'),
            'view' => ViewTdxMetronetCircuit::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            CostsRelationManager::class,
        ];
    }
}
