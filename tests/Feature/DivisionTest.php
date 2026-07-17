<?php

use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\QueryException;

test('division codes are globally unique across departments', function () {
    $departmentOne = Department::factory()->create();
    $departmentTwo = Department::factory()->create();

    Division::factory()->create(['department_code' => $departmentOne->code, 'code' => '117']);

    Division::factory()->create(['department_code' => $departmentTwo->code, 'code' => '117']);
})->throws(QueryException::class);

test('the same division code cannot be used twice under the same department', function () {
    $department = Department::factory()->create();

    Division::factory()->create(['department_code' => $department->code, 'code' => '117']);

    Division::factory()->create(['department_code' => $department->code, 'code' => '117']);
})->throws(QueryException::class);
