<?php

namespace App\Filament\Resources\BudgetLineItems\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GlAllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'glAllocations';

    protected static ?string $title = 'GL allocations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('gl_code_id')
                    ->label('GL code')
                    ->relationship('glCode', 'code_string')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('percent')
                    ->numeric()
                    ->suffix('%')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('glCode.code_string')
                    ->label('GL code')
                    ->sortable(),
                TextColumn::make('percent')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('usd')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
