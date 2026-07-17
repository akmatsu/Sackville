<?php

namespace App\Filament\Resources\SubObjectCodes;

use App\Filament\Clusters\ChartOfAccounts;
use App\Filament\Resources\SubObjectCodes\Pages\CreateSubObjectCode;
use App\Filament\Resources\SubObjectCodes\Pages\EditSubObjectCode;
use App\Filament\Resources\SubObjectCodes\Pages\ListSubObjectCodes;
use App\Filament\Resources\SubObjectCodes\Schemas\SubObjectCodeForm;
use App\Filament\Resources\SubObjectCodes\Tables\SubObjectCodesTable;
use App\Models\SubObjectCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubObjectCodeResource extends Resource
{
    protected static ?string $cluster = ChartOfAccounts::class;

    protected static ?string $model = SubObjectCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Sub-object code';

    protected static ?string $pluralModelLabel = 'Sub-object codes';

    public static function form(Schema $schema): Schema
    {
        return SubObjectCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubObjectCodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubObjectCodes::route('/'),
            'create' => CreateSubObjectCode::route('/create'),
            'edit' => EditSubObjectCode::route('/{record}/edit'),
        ];
    }
}
