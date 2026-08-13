<?php

namespace App\Filament\Imports;

use App\Models\BudgetCycle;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareModelCost;
use App\Models\HardwareReplacementGroup;
use App\Models\Vendor;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class HardwareModelImporter extends Importer
{
    protected static ?string $model = HardwareModel::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Model Name')
                ->guess(['Model'])
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Standard iPhone'),

            ImportColumn::make('vendor')
                ->rules(['nullable', 'string', 'max:255'])
                ->fillRecordUsing(fn () => null)
                ->helperText('Required only when the model doesn\'t already exist in the catalog.')
                ->example('Apple'),

            ImportColumn::make('unit_cost')
                ->label('Cost')
                ->requiredMapping()
                ->numeric(decimalPlaces: 2)
                ->rules(['required', 'numeric', 'min:0'])
                ->fillRecordUsing(fn () => null)
                ->example('599.00'),

            ImportColumn::make('fiscal_year')
                ->label('Fiscal Year')
                ->integer()
                ->rules(['nullable', 'integer', 'min:0'])
                ->fillRecordUsing(fn () => null)
                ->helperText('Defaults to the currently open budget cycle if left blank.')
                ->example('28'),
        ];
    }

    public function resolveRecord(): ?HardwareModel
    {
        $category = $this->resolveCategory();

        $model = HardwareModel::query()->firstOrNew([
            'name' => $this->data['name'],
            'hardware_category_id' => $category->id,
        ]);

        $vendorName = $this->data['vendor'] ?? null;

        if (filled($vendorName)) {
            $model->vendor_id = Vendor::query()->firstOrCreate(['name' => $vendorName])->id;
        } elseif (! $model->exists) {
            throw new RowImportFailedException('The "vendor" column is required when creating a new hardware model.');
        }

        return $model;
    }

    /**
     * Resolves the target hardware category from the owning replacement
     * group's replaceable categories, since the CSV has no category column.
     * The group must be linked to exactly one category for this to be
     * unambiguous.
     */
    protected function resolveCategory(): HardwareCategory
    {
        $group = HardwareReplacementGroup::query()->find($this->options['hardware_replacement_group_id'] ?? null);
        $replaceableCategories = $group?->replaceableCategories ?? collect();

        if ($replaceableCategories->count() === 1) {
            return $replaceableCategories->first();
        }

        throw new RowImportFailedException(
            'This replacement group has '
                .($replaceableCategories->isEmpty() ? 'no' : 'multiple')
                .' replaceable categories, so the target hardware category is ambiguous.'
        );
    }

    protected function afterSave(): void
    {
        $groupId = $this->options['hardware_replacement_group_id'] ?? null;

        if ($groupId !== null) {
            $this->record->hardwareReplacementGroups()->syncWithoutDetaching([$groupId]);
        }

        $fiscalYear = $this->data['fiscal_year'] ?? BudgetCycle::query()->open()->latest('fiscal_year')->value('fiscal_year');

        if ($fiscalYear === null) {
            throw new RowImportFailedException('No fiscal year was provided and there is no open budget cycle to default to.');
        }

        HardwareModelCost::query()->updateOrCreate(
            [
                'hardware_model_id' => $this->record->id,
                'fiscal_year' => $fiscalYear,
                'with_docking' => false,
            ],
            [
                'unit_cost' => $this->data['unit_cost'],
            ],
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your hardware model import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
