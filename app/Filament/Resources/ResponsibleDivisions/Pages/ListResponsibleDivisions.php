<?php

namespace App\Filament\Resources\ResponsibleDivisions\Pages;

use App\Filament\Resources\ResponsibleDivisions\ResponsibleDivisionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResponsibleDivisions extends ListRecords
{
    protected static string $resource = ResponsibleDivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
