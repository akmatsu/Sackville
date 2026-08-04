<?php

namespace App\Filament\Resources\HardwareReplacementGroups;

use App\Filament\Resources\HardwareReplacementGroups\Pages\CreateHardwareReplacementGroup;
use App\Filament\Resources\HardwareReplacementGroups\Pages\EditHardwareReplacementGroup;
use App\Filament\Resources\HardwareReplacementGroups\Pages\ListHardwareReplacementGroups;
use App\Filament\Resources\HardwareReplacementGroups\RelationManagers\EligibleModelsRelationManager;
use App\Filament\Resources\HardwareReplacementGroups\RelationManagers\ReplaceableCategoriesRelationManager;
use App\Filament\Resources\HardwareReplacementGroups\Schemas\HardwareReplacementGroupForm;
use App\Filament\Resources\HardwareReplacementGroups\Tables\HardwareReplacementGroupsTable;
use App\Models\HardwareReplacementGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HardwareReplacementGroupResource extends Resource
{
    protected static ?string $model = HardwareReplacementGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return HardwareReplacementGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HardwareReplacementGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReplaceableCategoriesRelationManager::class,
            EligibleModelsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHardwareReplacementGroups::route('/'),
            'create' => CreateHardwareReplacementGroup::route('/create'),
            'edit' => EditHardwareReplacementGroup::route('/{record}/edit'),
        ];
    }
}
