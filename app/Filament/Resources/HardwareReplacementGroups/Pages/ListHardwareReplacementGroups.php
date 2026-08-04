<?php

namespace App\Filament\Resources\HardwareReplacementGroups\Pages;

use App\Filament\Resources\HardwareReplacementGroups\HardwareReplacementGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHardwareReplacementGroups extends ListRecords
{
    protected static string $resource = HardwareReplacementGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
