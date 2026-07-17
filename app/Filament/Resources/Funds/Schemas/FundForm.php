<?php

namespace App\Filament\Resources\Funds\Schemas;

use App\Enums\FundType;
use App\Models\Fund;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FundForm
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
                    ->disabled(fn (?Fund $record): bool => $record !== null)
                    ->dehydrated()
                    ->helperText('Cannot be changed after creation — other tables reference this code.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('fund_type')
                    ->label('Fund type')
                    ->options(FundType::class)
                    ->required(),
                Toggle::make('active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
