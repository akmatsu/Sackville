<?php

namespace App\Filament\Resources\ObjectCodes\Pages;

use App\Filament\Resources\ObjectCodes\ObjectCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObjectCodes extends ListRecords
{
    protected static string $resource = ObjectCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
