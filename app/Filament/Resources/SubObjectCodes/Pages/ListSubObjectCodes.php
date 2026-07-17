<?php

namespace App\Filament\Resources\SubObjectCodes\Pages;

use App\Filament\Resources\SubObjectCodes\SubObjectCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubObjectCodes extends ListRecords
{
    protected static string $resource = SubObjectCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
