<?php

namespace App\Filament\Resources\HardwareModels\Pages;

use App\Filament\Resources\HardwareModels\HardwareModelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHardwareModel extends EditRecord
{
    protected static string $resource = HardwareModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
