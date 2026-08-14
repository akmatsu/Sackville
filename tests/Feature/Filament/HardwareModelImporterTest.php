<?php

use App\Enums\BudgetCycleStatus;
use App\Filament\Imports\HardwareModelImporter;
use App\Models\BudgetCycle;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareModelCost;
use App\Models\HardwareReplacementGroup;
use App\Models\User;
use App\Models\Vendor;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

/**
 * @param  array<string, string|null>|null  $columnMap
 */
function makeHardwareModelImporter(HardwareReplacementGroup $group, ?array $columnMap = null): HardwareModelImporter
{
    $import = Import::create([
        'file_name' => 'models.csv',
        'file_path' => 'imports/models.csv',
        'importer' => HardwareModelImporter::class,
        'total_rows' => 1,
        'user_id' => User::factory()->create()->id,
    ]);

    $columnMap ??= [
        'name' => 'name',
        'vendor' => 'vendor',
        'unit_cost' => 'unit_cost',
        'fiscal_year' => 'fiscal_year',
    ];

    return $import->getImporter($columnMap, [
        'hardware_replacement_group_id' => $group->id,
    ]);
}

it('creates a new hardware model and cost row, attaching it to the group', function () {
    $category = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $group = HardwareReplacementGroup::factory()->create();
    $group->replaceableCategories()->attach($category);

    $importer = makeHardwareModelImporter($group);

    $importer([
        'name' => 'Standard iPad',
        'vendor' => 'Apple',
        'unit_cost' => '599.00',
        'fiscal_year' => '28',
    ]);

    $model = HardwareModel::where('name', 'Standard iPad')->first();

    expect($model)->not->toBeNull();
    expect($model->vendor->name)->toBe('Apple');
    expect($model->hardware_category_id)->toBe($category->id);
    expect($group->eligibleModels()->whereKey($model->id)->exists())->toBeTrue();

    assertDatabaseHas(HardwareModelCost::class, [
        'hardware_model_id' => $model->id,
        'fiscal_year' => 28,
        'unit_cost' => 599.00,
        'with_docking' => false,
    ]);
});

it('matches an existing hardware model by name and category instead of duplicating it', function () {
    $category = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $group = HardwareReplacementGroup::factory()->create();
    $group->replaceableCategories()->attach($category);

    $vendor = Vendor::factory()->create(['name' => 'Apple']);
    $existing = HardwareModel::factory()->create([
        'name' => 'Standard iPad',
        'hardware_category_id' => $category->id,
        'vendor_id' => $vendor->id,
    ]);

    $importer = makeHardwareModelImporter($group, [
        'name' => 'name',
        'unit_cost' => 'unit_cost',
        'fiscal_year' => 'fiscal_year',
    ]);

    $importer([
        'name' => 'Standard iPad',
        'unit_cost' => '650.00',
        'fiscal_year' => '28',
    ]);

    assertDatabaseCount(HardwareModel::class, 1);
    expect($existing->refresh()->vendor_id)->toBe($vendor->id);

    assertDatabaseHas(HardwareModelCost::class, [
        'hardware_model_id' => $existing->id,
        'fiscal_year' => 28,
        'unit_cost' => 650.00,
    ]);
});

it('defaults to the open budget cycle fiscal year when the CSV omits it', function () {
    $category = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $group = HardwareReplacementGroup::factory()->create();
    $group->replaceableCategories()->attach($category);

    BudgetCycle::factory()->create(['fiscal_year' => 29, 'status' => BudgetCycleStatus::Open]);

    $importer = makeHardwareModelImporter($group, [
        'name' => 'name',
        'vendor' => 'vendor',
        'unit_cost' => 'unit_cost',
    ]);

    $importer([
        'name' => 'Cradlepoint',
        'vendor' => 'Ericsson',
        'unit_cost' => '250.00',
    ]);

    $model = HardwareModel::where('name', 'Cradlepoint')->first();

    assertDatabaseHas(HardwareModelCost::class, [
        'hardware_model_id' => $model->id,
        'fiscal_year' => 29,
        'unit_cost' => 250.00,
    ]);
});

it('upserts the cost row instead of duplicating it on re-import for the same fiscal year', function () {
    $category = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $group = HardwareReplacementGroup::factory()->create();
    $group->replaceableCategories()->attach($category);

    $importer = makeHardwareModelImporter($group);

    $row = ['name' => 'Standard iPad', 'vendor' => 'Apple', 'unit_cost' => '599.00', 'fiscal_year' => '28'];

    $importer($row);
    $importer(array_merge($row, ['unit_cost' => '625.00']));

    assertDatabaseCount(HardwareModel::class, 1);
    assertDatabaseCount(HardwareModelCost::class, 1);
    assertDatabaseHas(HardwareModelCost::class, ['unit_cost' => 625.00]);
});

it('finds or creates a vendor by name instead of duplicating it', function () {
    $category = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $group = HardwareReplacementGroup::factory()->create();
    $group->replaceableCategories()->attach($category);

    $importer = makeHardwareModelImporter($group);

    $importer(['name' => 'Standard iPad', 'vendor' => 'Apple', 'unit_cost' => '599.00', 'fiscal_year' => '28']);
    $importer(['name' => 'Standard iPhone', 'vendor' => 'Apple', 'unit_cost' => '899.00', 'fiscal_year' => '28']);

    assertDatabaseCount(Vendor::class, 1);
});

it('fails the row when the group has no unambiguous replaceable category', function () {
    $group = HardwareReplacementGroup::factory()->create();

    $importer = makeHardwareModelImporter($group);

    expect(fn () => $importer(['name' => 'Standard iPad', 'vendor' => 'Apple', 'unit_cost' => '599.00', 'fiscal_year' => '28']))
        ->toThrow(RowImportFailedException::class);

    assertDatabaseCount(HardwareModel::class, 0);
});

it('fails the row when creating a new model without a vendor', function () {
    $category = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $group = HardwareReplacementGroup::factory()->create();
    $group->replaceableCategories()->attach($category);

    $importer = makeHardwareModelImporter($group, [
        'name' => 'name',
        'unit_cost' => 'unit_cost',
        'fiscal_year' => 'fiscal_year',
    ]);

    expect(fn () => $importer(['name' => 'Standard iPad', 'unit_cost' => '599.00', 'fiscal_year' => '28']))
        ->toThrow(RowImportFailedException::class);

    assertDatabaseCount(HardwareModel::class, 0);
});
