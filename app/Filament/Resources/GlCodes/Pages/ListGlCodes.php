<?php

namespace App\Filament\Resources\GlCodes\Pages;

use App\Filament\Resources\GlCodes\GlCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlCodes extends ListRecords
{
    protected static string $resource = GlCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
