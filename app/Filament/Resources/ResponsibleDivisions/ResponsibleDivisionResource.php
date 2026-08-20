<?php

namespace App\Filament\Resources\ResponsibleDivisions;

use App\Filament\Resources\ResponsibleDivisions\Pages\CreateResponsibleDivision;
use App\Filament\Resources\ResponsibleDivisions\Pages\EditResponsibleDivision;
use App\Filament\Resources\ResponsibleDivisions\Pages\ListResponsibleDivisions;
use App\Filament\Resources\ResponsibleDivisions\RelationManagers\LocationsRelationManager;
use App\Filament\Resources\ResponsibleDivisions\Schemas\ResponsibleDivisionForm;
use App\Filament\Resources\ResponsibleDivisions\Tables\ResponsibleDivisionsTable;
use App\Models\ResponsibleDivision;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ResponsibleDivisionResource extends Resource
{
    protected static ?string $model = ResponsibleDivision::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ResponsibleDivisionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResponsibleDivisionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResponsibleDivisions::route('/'),
            'create' => CreateResponsibleDivision::route('/create'),
            'edit' => EditResponsibleDivision::route('/{record}/edit'),
        ];
    }
}
