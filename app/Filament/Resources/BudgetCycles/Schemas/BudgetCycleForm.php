<?php

namespace App\Filament\Resources\BudgetCycles\Schemas;

use App\Enums\BudgetCycleStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BudgetCycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fiscal_year')
                    ->label('Fiscal year')
                    ->numeric()
                    ->required()
                    ->unique(ignoreRecord: true),
                DatePicker::make('opens_at')
                    ->label('Opens')
                    ->required(),
                DatePicker::make('closes_at')
                    ->label('Closes')
                    ->required(),
                Select::make('status')
                    ->options(BudgetCycleStatus::class)
                    ->default(BudgetCycleStatus::Draft)
                    ->required(),
            ]);
    }
}
