<?php

namespace App\Filament\Resources\GlCodes\Schemas;

use App\Enums\FundType;
use App\Enums\ObjectCodeCategory;
use App\Models\Division;
use App\Models\SubObjectCode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class GlCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Toggle::make('active')
                    ->default(true)
                    ->required(),
                Fieldset::make('GL Code')->columns(5)->columnSpan('full')->schema([
                    Select::make('fund_code')
                        ->label('Fund')
                        ->relationship('fund', 'name')
                        ->required()
                        ->live()
                        ->createOptionForm([
                            TextInput::make('code')
                                ->required()
                                ->maxLength(255)
                                ->unique(),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('fund_type')
                                ->options(FundType::class)
                                ->required(),
                            Toggle::make('active')
                                ->default(true)
                                ->required(),
                        ]),

                    Select::make('department_code')
                        ->label('Department')
                        ->relationship('department', 'name')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('division_id', null))
                        ->createOptionForm([
                            TextInput::make('code')
                                ->required()
                                ->maxLength(255)
                                ->unique(),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Toggle::make('active')
                                ->default(true)
                                ->required(),
                        ]),
                    Select::make('division_id')
                        ->label('Division')
                        ->relationship(
                            name: 'division',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query->where('department_code', $get('department_code')),
                        )
                        ->disabled(fn (Get $get): bool => blank($get('department_code')))
                        ->required(fn (Get $get): bool => filled($get('object_code')))
                        ->live()
                        ->createOptionForm([
                            TextInput::make('code')
                                ->required()
                                ->maxLength(255)
                                ->unique(),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Toggle::make('active')
                                ->default(true)
                                ->required(),
                        ])
                        ->createOptionUsing(function (array $data, Get $get): int {
                            return Division::query()->firstOrCreate(
                                ['code' => $data['code']],
                                ['department_code' => $get('department_code'), 'name' => $data['name'], 'active' => $data['active'] ?? true],
                            )->id;
                        }),

                    Select::make('object_code')
                        ->label('Object code')
                        ->relationship('objectCode', 'name')
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('sub_object_code_id', null))
                        ->createOptionForm([
                            TextInput::make('code')
                                ->required()
                                ->maxLength(255)
                                ->unique(),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('category')
                                ->options(ObjectCodeCategory::class)
                                ->required(),
                            Toggle::make('active')
                                ->default(true)
                                ->required(),
                        ]),
                    Select::make('sub_object_code_id')
                        ->label('Sub-object code')
                        ->relationship(
                            name: 'subObjectCode',
                            titleAttribute: 'code',
                            modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query->where('object_code', $get('object_code')),
                        )
                        ->disabled(fn (Get $get): bool => blank($get('object_code')))
                        ->createOptionForm([
                            TextInput::make('code')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Toggle::make('active')
                                ->default(true)
                                ->required(),
                        ])
                        ->createOptionUsing(function (array $data, Get $get): int {
                            return SubObjectCode::query()->firstOrCreate(
                                ['object_code' => $get('object_code'), 'code' => $data['code']],
                                ['name' => $data['name'], 'active' => $data['active'] ?? true],
                            )->id;
                        }),
                ]),

                TextInput::make('code_string'),

            ]);
    }
}
