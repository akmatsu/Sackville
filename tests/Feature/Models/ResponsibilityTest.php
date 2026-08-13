<?php

use App\Enums\ResponsibilityScopeType;
use App\Models\GlCode;
use App\Models\Responsibility;
use App\Models\TdxAsset;
use App\Models\User;

/**
 * A Responsibility with scope_type Department/Fund/Object/SpecificGl but a
 * null scope_value is an invalid/incomplete row — it must match nothing,
 * not fall through to matching assets whose compared column is also null.
 * Regression coverage for a hole where matchesAsset()/scopeVisibleTo() used
 * `$asset->column === null` (or the SQL equivalent, which Laravel's query
 * builder turns a `where('column', null)` into a `whereNull`) as a match.
 */
dataset('nullable-value scope types', [
    'Department' => [ResponsibilityScopeType::Department, ['assigned_department_code' => null]],
    'Fund' => [ResponsibilityScopeType::Fund, ['gl_code_id' => null]],
    'Object' => [ResponsibilityScopeType::Object, ['gl_code_id' => null]],
    'SpecificGl' => [ResponsibilityScopeType::SpecificGl, ['gl_code_id' => null]],
]);

test('matchesAsset() does not match when scope_value is null, even against an asset with a null compared value', function (ResponsibilityScopeType $scopeType, array $assetOverrides) {
    $responsibility = Responsibility::factory()->create([
        'scope_type' => $scopeType,
        'scope_value' => null,
    ]);

    $asset = TdxAsset::factory()->create($assetOverrides);

    expect($responsibility->matchesAsset($asset))->toBeFalse();
})->with('nullable-value scope types');

test('scopeVisibleTo() does not surface assets when scope_value is null, even against an asset with a null compared value', function (ResponsibilityScopeType $scopeType, array $assetOverrides) {
    $user = User::factory()->create();
    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => $scopeType,
        'scope_value' => null,
    ]);

    TdxAsset::factory()->create($assetOverrides);

    expect(TdxAsset::query()->visibleTo($user)->count())->toBe(0);
})->with('nullable-value scope types');

test('an object-scoped responsibility with a null scope_value does not match an asset whose gl code has no object segment', function () {
    $responsibility = Responsibility::factory()->create([
        'scope_type' => ResponsibilityScopeType::Object,
        'scope_value' => null,
    ]);

    $glCode = GlCode::factory()->divisionRollup()->create();
    $asset = TdxAsset::factory()->create(['gl_code_id' => $glCode->id]);

    expect($responsibility->matchesAsset($asset))->toBeFalse();
});

test('a department-scoped responsibility still matches when scope_value is set', function () {
    $responsibility = Responsibility::factory()->create([
        'scope_type' => ResponsibilityScopeType::Department,
        'scope_value' => 'Information Technology',
    ]);

    $asset = TdxAsset::factory()->create(['assigned_department_code' => 'Information Technology']);

    expect($responsibility->matchesAsset($asset))->toBeTrue();
});
