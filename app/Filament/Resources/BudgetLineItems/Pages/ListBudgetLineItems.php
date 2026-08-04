<?php

namespace App\Filament\Resources\BudgetLineItems\Pages;

use App\Filament\Resources\BudgetLineItems\BudgetLineItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetLineItems extends ListRecords
{
    protected static string $resource = BudgetLineItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
