<?php

namespace App\Filament\Resources\TdxMobilePlans;

use App\Filament\Resources\TdxMobilePlans\Pages\ListTdxMobilePlans;
use App\Filament\Resources\TdxMobilePlans\Pages\ViewTdxMobilePlan;
use App\Filament\Resources\TdxMobilePlans\RelationManagers\DevicesRelationManager;
use App\Filament\Resources\TdxMobilePlans\Schemas\TdxMobilePlanInfolist;
use App\Filament\Resources\TdxMobilePlans\Tables\TdxMobilePlansTable;
use App\Models\TdxMobilePlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TdxMobilePlanResource extends Resource
{
    protected static ?string $model = TdxMobilePlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Mobile Plans';

    protected static ?string $modelLabel = 'mobile plan';

    protected static ?string $pluralModelLabel = 'mobile plans';

    protected static ?string $recordTitleAttribute = 'asset_tag';

    public static function infolist(Schema $schema): Schema
    {
        return TdxMobilePlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TdxMobilePlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DevicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTdxMobilePlans::route('/'),
            'view' => ViewTdxMobilePlan::route('/{record}'),
        ];
    }
}
