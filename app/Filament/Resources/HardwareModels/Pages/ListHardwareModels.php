<?php

namespace App\Filament\Resources\HardwareModels\Pages;

use App\Filament\Resources\HardwareModels\HardwareModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHardwareModels extends ListRecords
{
    protected static string $resource = HardwareModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
