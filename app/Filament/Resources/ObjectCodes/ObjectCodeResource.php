<?php

namespace App\Filament\Resources\ObjectCodes;

use App\Filament\Clusters\ChartOfAccounts;
use App\Filament\Resources\ObjectCodes\Pages\CreateObjectCode;
use App\Filament\Resources\ObjectCodes\Pages\EditObjectCode;
use App\Filament\Resources\ObjectCodes\Pages\ListObjectCodes;
use App\Filament\Resources\ObjectCodes\RelationManagers\SubObjectCodesRelationManager;
use App\Filament\Resources\ObjectCodes\Schemas\ObjectCodeForm;
use App\Filament\Resources\ObjectCodes\Tables\ObjectCodesTable;
use App\Models\ObjectCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ObjectCodeResource extends Resource
{
    protected static ?string $cluster = ChartOfAccounts::class;

    protected static ?string $model = ObjectCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Object code';

    protected static ?string $pluralModelLabel = 'Object codes';

    public static function form(Schema $schema): Schema
    {
        return ObjectCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObjectCodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SubObjectCodesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObjectCodes::route('/'),
            'create' => CreateObjectCode::route('/create'),
            'edit' => EditObjectCode::route('/{record}/edit'),
        ];
    }
}
