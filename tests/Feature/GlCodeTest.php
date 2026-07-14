<?php

use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\ObjectCode;
use App\Models\SubObjectCode;
use Illuminate\Database\QueryException;

test('code string is built from segments at the department rollup granularity', function () {
    $fund = Fund::factory()->create(['code' => '100']);
    $department = Department::factory()->create(['code' => '115']);

    $glCode = GlCode::factory()->create([
        'fund_code' => $fund->code,
        'department_code' => $department->code,
        'division_id' => null,
        'object_code' => null,
        'sub_object_code_id' => null,
    ]);

    expect($glCode->code_string)->toBe('100.115');
});

test('code string is built from segments at the division rollup granularity', function () {
    $fund = Fund::factory()->create(['code' => '100']);
    $department = Department::factory()->create(['code' => '115']);
    $division = Division::factory()->create(['department_code' => $department->code, 'code' => '117']);

    $glCode = GlCode::factory()->divisionRollup()->create([
        'fund_code' => $fund->code,
        'department_code' => $department->code,
        'division_id' => $division->id,
    ]);

    expect($glCode->code_string)->toBe('100.115.117');
});

test('code string is built from segments at the budget line rollup granularity', function () {
    $fund = Fund::factory()->create(['code' => '100']);
    $department = Department::factory()->create(['code' => '115']);
    $division = Division::factory()->create(['department_code' => $department->code, 'code' => '117']);
    $objectCode = ObjectCode::factory()->create(['code' => '434']);

    $glCode = GlCode::factory()->budgetLineRollup()->create([
        'fund_code' => $fund->code,
        'department_code' => $department->code,
        'division_id' => $division->id,
        'object_code' => $objectCode->code,
    ]);

    expect($glCode->code_string)->toBe('100.115.117.434');
});

test('code string is built from segments at the transaction level granularity', function () {
    $fund = Fund::factory()->create(['code' => '100']);
    $department = Department::factory()->create(['code' => '115']);
    $division = Division::factory()->create(['department_code' => $department->code, 'code' => '117']);
    $objectCode = ObjectCode::factory()->create(['code' => '434']);
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code, 'code' => '100']);

    $glCode = GlCode::factory()->create([
        'fund_code' => $fund->code,
        'department_code' => $department->code,
        'division_id' => $division->id,
        'object_code' => $objectCode->code,
        'sub_object_code_id' => $subObjectCode->id,
    ]);

    expect($glCode->code_string)->toBe('100.115.117.434.100');
});

test('an object code cannot be set without a division', function () {
    $glCode = GlCode::factory()->divisionRollup()->make([
        'division_id' => null,
    ]);
    $glCode->object_code = ObjectCode::factory()->create()->code;

    $glCode->save();
})->throws(InvalidArgumentException::class);

test('a sub object code cannot be set without an object code', function () {
    $glCode = GlCode::factory()->budgetLineRollup()->make([
        'object_code' => null,
    ]);
    $glCode->sub_object_code_id = SubObjectCode::factory()->create()->id;

    $glCode->save();
})->throws(InvalidArgumentException::class);

test('the code string is unique', function () {
    $glCode = GlCode::factory()->divisionRollup()->create();

    GlCode::factory()->divisionRollup()->create([
        'fund_code' => $glCode->fund_code,
        'department_code' => $glCode->department_code,
        'division_id' => $glCode->division_id,
    ]);
})->throws(QueryException::class);
