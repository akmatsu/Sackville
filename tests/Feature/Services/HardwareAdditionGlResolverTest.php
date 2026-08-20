<?php

use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\ObjectCode;
use App\Models\ResponsibleDivision;
use App\Models\SubObjectCode;
use App\Models\TdxAsset;
use App\Services\HardwareAdditionGlResolver;

test('resolves the gl code from the division\'s existing assets and the category\'s default object/sub-object', function () {
    $division = ResponsibleDivision::factory()->create();
    $referenceGlCode = GlCode::factory()->create();

    TdxAsset::factory()->create([
        'responsible_division_id' => $division->id,
        'gl_code_id' => $referenceGlCode->id,
    ]);

    $objectCode = ObjectCode::factory()->create();
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code]);

    $targetGlCode = GlCode::factory()->create([
        'fund_code' => $referenceGlCode->fund_code,
        'department_code' => $referenceGlCode->department_code,
        'division_id' => $referenceGlCode->division_id,
        'object_code' => $objectCode->code,
        'sub_object_code_id' => $subObjectCode->id,
    ]);

    $category = HardwareCategory::factory()->create([
        'default_object_code' => $objectCode->code,
        'default_sub_object_code_id' => $subObjectCode->id,
    ]);

    $resolved = app(HardwareAdditionGlResolver::class)->resolve($division, $category);

    expect($resolved?->id)->toBe($targetGlCode->id);
});

test('returns null when the category has no default object/sub-object configured', function () {
    $division = ResponsibleDivision::factory()->create();
    $referenceGlCode = GlCode::factory()->create();

    TdxAsset::factory()->create([
        'responsible_division_id' => $division->id,
        'gl_code_id' => $referenceGlCode->id,
    ]);

    $category = HardwareCategory::factory()->create();

    expect(app(HardwareAdditionGlResolver::class)->resolve($division, $category))->toBeNull();
});

test('returns null when the division has no existing gl-coded assets', function () {
    $division = ResponsibleDivision::factory()->create();

    $objectCode = ObjectCode::factory()->create();
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code]);

    $category = HardwareCategory::factory()->create([
        'default_object_code' => $objectCode->code,
        'default_sub_object_code_id' => $subObjectCode->id,
    ]);

    expect(app(HardwareAdditionGlResolver::class)->resolve($division, $category))->toBeNull();
});

test('returns null when the derived five-segment gl code does not exist yet', function () {
    $division = ResponsibleDivision::factory()->create();
    $referenceGlCode = GlCode::factory()->create();

    TdxAsset::factory()->create([
        'responsible_division_id' => $division->id,
        'gl_code_id' => $referenceGlCode->id,
    ]);

    $objectCode = ObjectCode::factory()->create();
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code]);

    $category = HardwareCategory::factory()->create([
        'default_object_code' => $objectCode->code,
        'default_sub_object_code_id' => $subObjectCode->id,
    ]);

    // No GlCode row exists combining the reference's fund/department/division
    // with the category's object/sub-object segments.
    expect(app(HardwareAdditionGlResolver::class)->resolve($division, $category))->toBeNull();
});

test('prefers the gl code used by the most of the division\'s existing assets', function () {
    $division = ResponsibleDivision::factory()->create();

    $minorityGlCode = GlCode::factory()->create();
    $majorityGlCode = GlCode::factory()->create();

    TdxAsset::factory()->create(['responsible_division_id' => $division->id, 'gl_code_id' => $minorityGlCode->id]);
    TdxAsset::factory()->count(2)->create(['responsible_division_id' => $division->id, 'gl_code_id' => $majorityGlCode->id]);

    $objectCode = ObjectCode::factory()->create();
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code]);

    $targetGlCode = GlCode::factory()->create([
        'fund_code' => $majorityGlCode->fund_code,
        'department_code' => $majorityGlCode->department_code,
        'division_id' => $majorityGlCode->division_id,
        'object_code' => $objectCode->code,
        'sub_object_code_id' => $subObjectCode->id,
    ]);

    $category = HardwareCategory::factory()->create([
        'default_object_code' => $objectCode->code,
        'default_sub_object_code_id' => $subObjectCode->id,
    ]);

    $resolved = app(HardwareAdditionGlResolver::class)->resolve($division, $category);

    expect($resolved?->id)->toBe($targetGlCode->id);
});
