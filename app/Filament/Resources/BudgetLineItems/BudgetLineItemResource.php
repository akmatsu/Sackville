<?php

namespace App\Filament\Resources\BudgetLineItems;

use App\Filament\Resources\BudgetLineItems\Pages\CreateBudgetLineItem;
use App\Filament\Resources\BudgetLineItems\Pages\EditBudgetLineItem;
use App\Filament\Resources\BudgetLineItems\Pages\ListBudgetLineItems;
use App\Filament\Resources\BudgetLineItems\RelationManagers\GlAllocationsRelationManager;
use App\Filament\Resources\BudgetLineItems\Schemas\BudgetLineItemForm;
use App\Filament\Resources\BudgetLineItems\Tables\BudgetLineItemsTable;
use App\Models\BudgetLineItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BudgetLineItemResource extends Resource
{
    protected static ?string $model = BudgetLineItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Budgeting';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return BudgetLineItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetLineItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            GlAllocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgetLineItems::route('/'),
            'create' => CreateBudgetLineItem::route('/create'),
            'edit' => EditBudgetLineItem::route('/{record}/edit'),
        ];
    }
}
