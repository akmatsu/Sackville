<?php

use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareReplacementGroup;
use Database\Seeders\HardwareReplacementGroupsSeeder;

it('creates missing hardware categories instead of failing when run in isolation', function () {
    // No HardwareCategory rows exist yet — simulates this seeder running
    // before (or without) HardwareCategoriesSeeder, which previously threw
    // a fatal error accessing ->id on a null category.
    expect(HardwareCategory::query()->count())->toBe(0);

    (new HardwareReplacementGroupsSeeder)->run();

    $workstationCategory = HardwareCategory::query()->where('name', 'Workstation')->first();
    $mobileCategory = HardwareCategory::query()->where('name', 'Mobile')->first();

    expect($workstationCategory)->not->toBeNull();
    expect($mobileCategory)->not->toBeNull();

    $workstationGroup = HardwareReplacementGroup::query()->where('name', 'Workstation')->first();
    $mobileGroup = HardwareReplacementGroup::query()->where('name', 'Mobile')->first();

    expect($workstationGroup->replaceableCategories()->whereKey($workstationCategory->id)->exists())->toBeTrue();
    expect($mobileGroup->replaceableCategories()->whereKey($mobileCategory->id)->exists())->toBeTrue();
});

it('reuses an existing hardware category instead of duplicating it', function () {
    $category = HardwareCategory::factory()->create(['name' => 'Workstation']);

    (new HardwareReplacementGroupsSeeder)->run();

    expect(HardwareCategory::query()->where('name', 'Workstation')->count())->toBe(1);

    $group = HardwareReplacementGroup::query()->where('name', 'Workstation')->first();

    expect($group->replaceableCategories()->whereKey($category->id)->exists())->toBeTrue();
});

it('links existing hardware models to their replacement group by name', function () {
    $category = HardwareCategory::factory()->create(['name' => 'Workstation']);
    $model = HardwareModel::factory()->create([
        'name' => 'Standard Desktop',
        'hardware_category_id' => $category->id,
    ]);

    (new HardwareReplacementGroupsSeeder)->run();

    $group = HardwareReplacementGroup::query()->where('name', 'Workstation')->first();

    expect($group->eligibleModels()->whereKey($model->id)->exists())->toBeTrue();
});
