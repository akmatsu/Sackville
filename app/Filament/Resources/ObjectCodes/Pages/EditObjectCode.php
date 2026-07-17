<?php

namespace App\Filament\Resources\ObjectCodes\Pages;

use App\Filament\Resources\ObjectCodes\ObjectCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObjectCode extends EditRecord
{
    protected static string $resource = ObjectCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
