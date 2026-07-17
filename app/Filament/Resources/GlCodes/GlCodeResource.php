<?php

namespace App\Filament\Resources\GlCodes;

use App\Filament\Clusters\ChartOfAccounts;
use App\Filament\Resources\GlCodes\Pages\CreateGlCode;
use App\Filament\Resources\GlCodes\Pages\EditGlCode;
use App\Filament\Resources\GlCodes\Pages\ListGlCodes;
use App\Filament\Resources\GlCodes\Schemas\GlCodeForm;
use App\Filament\Resources\GlCodes\Tables\GlCodesTable;
use App\Models\GlCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GlCodeResource extends Resource
{
    protected static ?string $cluster = ChartOfAccounts::class;

    protected static ?string $model = GlCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code_string';

    protected static ?string $modelLabel = 'GL code';

    protected static ?string $pluralModelLabel = 'GL codes';

    public static function form(Schema $schema): Schema
    {
        return GlCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GlCodesTable::configure($table);
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
            'index' => ListGlCodes::route('/'),
            'create' => CreateGlCode::route('/create'),
            'edit' => EditGlCode::route('/{record}/edit'),
        ];
    }
}
