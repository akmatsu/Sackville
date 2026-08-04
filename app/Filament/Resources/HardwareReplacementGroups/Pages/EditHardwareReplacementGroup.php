<?php

namespace App\Filament\Resources\HardwareReplacementGroups\Pages;

use App\Filament\Resources\HardwareReplacementGroups\HardwareReplacementGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHardwareReplacementGroup extends EditRecord
{
    protected static string $resource = HardwareReplacementGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
