<?php

namespace App\Filament\Resources\GlCodes\Pages;

use App\Filament\Resources\GlCodes\GlCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlCode extends EditRecord
{
    protected static string $resource = GlCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
