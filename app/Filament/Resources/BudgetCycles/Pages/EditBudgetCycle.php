<?php

namespace App\Filament\Resources\BudgetCycles\Pages;

use App\Filament\Resources\BudgetCycles\BudgetCycleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudgetCycle extends EditRecord
{
    protected static string $resource = BudgetCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
