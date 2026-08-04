<?php

namespace App\Filament\Resources\HardwareCategories;

use App\Filament\Resources\HardwareCategories\Pages\CreateHardwareCategory;
use App\Filament\Resources\HardwareCategories\Pages\EditHardwareCategory;
use App\Filament\Resources\HardwareCategories\Pages\ListHardwareCategories;
use App\Filament\Resources\HardwareCategories\RelationManagers\HardwareModelsRelationManager;
use App\Filament\Resources\HardwareCategories\Schemas\HardwareCategoryForm;
use App\Filament\Resources\HardwareCategories\Tables\HardwareCategoriesTable;
use App\Models\HardwareCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HardwareCategoryResource extends Resource
{
    protected static ?string $model = HardwareCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Hardware category';

    protected static ?string $pluralModelLabel = 'Hardware categories';

    public static function form(Schema $schema): Schema
    {
        return HardwareCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HardwareCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            HardwareModelsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHardwareCategories::route('/'),
            'create' => CreateHardwareCategory::route('/create'),
            'edit' => EditHardwareCategory::route('/{record}/edit'),
        ];
    }
}
