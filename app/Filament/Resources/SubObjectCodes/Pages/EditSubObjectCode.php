<?php

namespace App\Filament\Resources\SubObjectCodes\Pages;

use App\Filament\Resources\SubObjectCodes\SubObjectCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubObjectCode extends EditRecord
{
    protected static string $resource = SubObjectCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
