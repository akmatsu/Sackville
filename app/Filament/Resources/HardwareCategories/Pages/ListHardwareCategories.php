<?php

namespace App\Filament\Resources\HardwareCategories\Pages;

use App\Filament\Resources\HardwareCategories\HardwareCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHardwareCategories extends ListRecords
{
    protected static string $resource = HardwareCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
