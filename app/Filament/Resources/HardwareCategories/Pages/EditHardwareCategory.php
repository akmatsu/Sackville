<?php

namespace App\Filament\Resources\HardwareCategories\Pages;

use App\Filament\Resources\HardwareCategories\HardwareCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHardwareCategory extends EditRecord
{
    protected static string $resource = HardwareCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
