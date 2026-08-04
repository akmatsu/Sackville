<?php

namespace App\Filament\Resources\BudgetCycles\Pages;

use App\Filament\Resources\BudgetCycles\BudgetCycleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetCycles extends ListRecords
{
    protected static string $resource = BudgetCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
