<?php

namespace App\Filament\Resources\BudgetLineItems\Pages;

use App\Filament\Resources\BudgetLineItems\BudgetLineItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBudgetLineItem extends CreateRecord
{
    protected static string $resource = BudgetLineItemResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();

        return $data;
    }
}
