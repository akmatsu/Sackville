<?php

namespace App\Filament\Resources\TdxAssets;

use App\Filament\Resources\TdxAssets\Pages\ListTdxAssets;
use App\Filament\Resources\TdxAssets\Pages\ViewTdxAsset;
use App\Filament\Resources\TdxAssets\Schemas\TdxAssetInfolist;
use App\Filament\Resources\TdxAssets\Tables\TdxAssetsTable;
use App\Models\TdxAsset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TdxAssetResource extends Resource
{
    protected static ?string $model = TdxAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'TDX Assets';

    protected static ?string $modelLabel = 'TDX asset';

    protected static ?string $pluralModelLabel = 'TDX assets';

    protected static ?string $recordTitleAttribute = 'asset_tag';

    public static function infolist(Schema $schema): Schema
    {
        return TdxAssetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TdxAssetsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTdxAssets::route('/'),
            'view' => ViewTdxAsset::route('/{record}'),
        ];
    }
}
