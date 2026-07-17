<?php

namespace App\Filament\Resources\ObjectCodes\Schemas;

use App\Enums\ObjectCodeCategory;
use App\Models\ObjectCode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ObjectCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?ObjectCode $record): bool => $record !== null)
                    ->dehydrated()
                    ->helperText('Cannot be changed after creation — other tables reference this code.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('category')
                    ->options(ObjectCodeCategory::class)
                    ->required(),
                Toggle::make('active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
