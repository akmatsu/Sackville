<?php

namespace App\Filament\Resources\ResponsibleDivisions\Pages;

use App\Filament\Resources\ResponsibleDivisions\ResponsibleDivisionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResponsibleDivision extends EditRecord
{
    protected static string $resource = ResponsibleDivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
