<?php

namespace App\Filament\Resources\BudgetLineItems\Schemas;

use App\Enums\BudgetLineItemStatus;
use App\Enums\BudgetLineItemType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BudgetLineItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('budget_cycle_id')
                    ->label('Budget cycle')
                    ->relationship('cycle', 'fiscal_year')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('item_type')
                    ->label('Item type')
                    ->options(BudgetLineItemType::class)
                    ->required()
                    ->live(),
                Select::make('tdx_asset_id')
                    ->label('TDX asset')
                    ->relationship('tdxAsset', 'asset_tag')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => self::itemType($get) === BudgetLineItemType::HardwareReplacement),
                Select::make('hardware_model_id')
                    ->label('Hardware model')
                    ->relationship('hardwareModel', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array(self::itemType($get), [
                        BudgetLineItemType::HardwareReplacement,
                        BudgetLineItemType::HardwareAddition,
                    ], true)),
                Select::make('software_product_id')
                    ->label('Software product')
                    ->relationship('softwareProduct', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => self::itemType($get) === BudgetLineItemType::Software),
                Toggle::make('with_docking')
                    ->label('With docking')
                    ->default(false)
                    ->visible(fn (Get $get): bool => in_array(self::itemType($get), [
                        BudgetLineItemType::HardwareReplacement,
                        BudgetLineItemType::HardwareAddition,
                    ], true)),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->required(),
                TextInput::make('previous_cost')
                    ->label('Previous cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('proposed_cost')
                    ->label('Proposed cost')
                    ->numeric()
                    ->prefix('$'),
                Select::make('status')
                    ->options(BudgetLineItemStatus::class)
                    ->default(BudgetLineItemStatus::NotStarted)
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('justification')
                    ->columnSpanFull(),
            ]);
    }

    private static function itemType(Get $get): ?BudgetLineItemType
    {
        $value = $get('item_type');

        return $value instanceof BudgetLineItemType ? $value : BudgetLineItemType::tryFrom((string) $value);
    }
}
