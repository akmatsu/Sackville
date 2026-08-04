<?php

namespace App\Filament\Resources\HardwareModels;

use App\Filament\Resources\HardwareModels\Pages\CreateHardwareModel;
use App\Filament\Resources\HardwareModels\Pages\EditHardwareModel;
use App\Filament\Resources\HardwareModels\Pages\ListHardwareModels;
use App\Filament\Resources\HardwareModels\RelationManagers\CostsRelationManager;
use App\Filament\Resources\HardwareModels\Schemas\HardwareModelForm;
use App\Filament\Resources\HardwareModels\Tables\HardwareModelsTable;
use App\Models\HardwareModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HardwareModelResource extends Resource
{
    protected static ?string $model = HardwareModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return HardwareModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HardwareModelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CostsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHardwareModels::route('/'),
            'create' => CreateHardwareModel::route('/create'),
            'edit' => EditHardwareModel::route('/{record}/edit'),
        ];
    }
}
