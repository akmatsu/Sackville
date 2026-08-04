<?php

namespace App\Filament\Resources\BudgetLineItems\Pages;

use App\Filament\Resources\BudgetLineItems\BudgetLineItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudgetLineItem extends EditRecord
{
    protected static string $resource = BudgetLineItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['last_modified_by_id'] = auth()->id();

        return $data;
    }
}
