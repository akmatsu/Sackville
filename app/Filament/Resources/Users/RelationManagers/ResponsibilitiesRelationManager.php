<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\ResponsibilityRole;
use App\Enums\ResponsibilityScopeType;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsibilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'responsibilities';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('scope_type')
                    ->label('Scope type')
                    ->options(ResponsibilityScopeType::class)
                    ->required()
                    ->live(),
                TextInput::make('scope_value')
                    ->label('Scope value')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => in_array($get('scope_type'), [
                        ResponsibilityScopeType::Fund,
                        ResponsibilityScopeType::Object,
                        ResponsibilityScopeType::SpecificGl,
                    ], true))
                    ->dehydratedWhenHidden()
                    ->helperText('The fund, object, or GL code this scope applies to, matching the selected scope type.'),
                Select::make('department_scope_value')
                    ->label('Department')
                    ->options(fn (): array => ResponsibleDivision::query()
                        ->whereNotNull('department_name')
                        ->distinct()
                        ->orderBy('department_name')
                        ->pluck('department_name', 'department_name')
                        ->all())
                    ->searchable()
                    ->live()
                    ->afterStateHydrated(function (Select $component, ?Responsibility $record): void {
                        if ($record?->scope_type === ResponsibilityScopeType::Department) {
                            $component->state($record->scope_value);
                        }
                    })
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('scope_value', $state))
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => $get('scope_type') === ResponsibilityScopeType::Department),
                Select::make('responsible_division_id')
                    ->label('Division')
                    ->options(fn (): array => ResponsibleDivision::query()
                        ->orderBy('department_name')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (ResponsibleDivision $division): array => [
                            $division->id => "{$division->department_name} — {$division->name}",
                        ])
                        ->all())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('scope_type') === ResponsibilityScopeType::Division)
                    ->dehydratedWhenHidden(),
                Select::make('responsible_location_id')
                    ->label('Location')
                    ->options(fn (): array => ResponsibleLocation::query()
                        ->with('division')
                        ->get()
                        ->mapWithKeys(fn (ResponsibleLocation $location): array => [
                            $location->id => "{$location->division->department_name} — {$location->division->name} — {$location->name}",
                        ])
                        ->all())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('scope_type') === ResponsibilityScopeType::Location)
                    ->dehydratedWhenHidden(),
                Select::make('role')
                    ->options(ResponsibilityRole::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scope_type')
                    ->label('Scope type')
                    ->badge(),
                TextColumn::make('scope_value')
                    ->label('Scope value')
                    ->state(function (Responsibility $record): ?string {
                        return match ($record->scope_type) {
                            ResponsibilityScopeType::Division => $record->responsibleDivision
                                ? "{$record->responsibleDivision->department_name} — {$record->responsibleDivision->name}"
                                : null,
                            ResponsibilityScopeType::Location => $record->responsibleLocation
                                ? "{$record->responsibleLocation->division->department_name} — {$record->responsibleLocation->division->name} — {$record->responsibleLocation->name}"
                                : null,
                            default => $record->scope_value,
                        };
                    }),
                TextColumn::make('role')
                    ->badge(),
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
