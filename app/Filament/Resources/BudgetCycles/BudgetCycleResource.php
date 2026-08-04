<?php

namespace App\Filament\Resources\BudgetCycles;

use App\Filament\Resources\BudgetCycles\Pages\CreateBudgetCycle;
use App\Filament\Resources\BudgetCycles\Pages\EditBudgetCycle;
use App\Filament\Resources\BudgetCycles\Pages\ListBudgetCycles;
use App\Filament\Resources\BudgetCycles\RelationManagers\LineItemsRelationManager;
use App\Filament\Resources\BudgetCycles\Schemas\BudgetCycleForm;
use App\Filament\Resources\BudgetCycles\Tables\BudgetCyclesTable;
use App\Models\BudgetCycle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BudgetCycleResource extends Resource
{
    protected static ?string $model = BudgetCycle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Budgeting';

    protected static ?string $recordTitleAttribute = 'fiscal_year';

    public static function form(Schema $schema): Schema
    {
        return BudgetCycleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetCyclesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LineItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgetCycles::route('/'),
            'create' => CreateBudgetCycle::route('/create'),
            'edit' => EditBudgetCycle::route('/{record}/edit'),
        ];
    }
}
