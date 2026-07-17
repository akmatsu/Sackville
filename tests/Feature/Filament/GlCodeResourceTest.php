<?php

use App\Filament\Resources\GlCodes\Pages\CreateGlCode;
use App\Filament\Resources\GlCodes\Pages\EditGlCode;
use App\Filament\Resources\GlCodes\Pages\ListGlCodes;
use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\ObjectCode;
use App\Models\SubObjectCode;
use App\Models\User;
use App\Support\Gl\GlCodeString;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists gl codes', function () {
    $glCodes = GlCode::factory()->count(3)->create();

    livewire(ListGlCodes::class)
        ->assertCanSeeTableRecords($glCodes);
});

it('creates a fully specified gl code and computes the code string', function () {
    $fund = Fund::factory()->create();
    $department = Department::factory()->create();
    $division = Division::factory()->create(['department_code' => $department->code]);
    $objectCode = ObjectCode::factory()->create();
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code]);

    livewire(CreateGlCode::class)
        ->fillForm([
            'fund_code' => $fund->code,
            'department_code' => $department->code,
            'division_id' => $division->id,
            'object_code' => $objectCode->code,
            'sub_object_code_id' => $subObjectCode->id,
            'label' => 'IT equipment replacement',
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $expected = GlCodeString::build($fund->code, $department->code, $division->code, $objectCode->code, $subObjectCode->code);

    assertDatabaseHas(GlCode::class, [
        'fund_code' => $fund->code,
        'department_code' => $department->code,
        'code_string' => $expected,
        'label' => 'IT equipment replacement',
    ]);
});

it('creates a division-rollup gl code without an object or sub-object segment', function () {
    $fund = Fund::factory()->create();
    $department = Department::factory()->create();
    $division = Division::factory()->create(['department_code' => $department->code]);

    livewire(CreateGlCode::class)
        ->fillForm([
            'fund_code' => $fund->code,
            'department_code' => $department->code,
            'division_id' => $division->id,
            'label' => 'Division rollup',
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $expected = GlCodeString::build($fund->code, $department->code, $division->code);

    assertDatabaseHas(GlCode::class, [
        'code_string' => $expected,
        'object_code' => null,
        'sub_object_code_id' => null,
    ]);
});

it('requires fund, department, and label to create a gl code', function () {
    livewire(CreateGlCode::class)
        ->fillForm(['fund_code' => '', 'department_code' => '', 'label' => ''])
        ->call('create')
        ->assertHasFormErrors(['fund_code' => 'required', 'department_code' => 'required', 'label' => 'required']);
});

it('updates a gl code and recomputes the code string', function () {
    $glCode = GlCode::factory()->create();
    $newFund = Fund::factory()->create();

    livewire(EditGlCode::class, ['record' => $glCode->getKey()])
        ->fillForm(['fund_code' => $newFund->code])
        ->call('save')
        ->assertHasNoFormErrors();

    $glCode->refresh();

    expect($glCode->fund_code)->toBe($newFund->code);
    expect($glCode->code_string)->toStartWith($newFund->code.'.');
});

it('quick-creates a fund inline from the fund select', function () {
    livewire(CreateGlCode::class)
        ->callFormComponentAction('fund_code', 'createOption', data: [
            'code' => '510',
            'name' => 'Water Utility',
            'fund_type' => 'enterprise',
            'active' => true,
        ])
        ->assertFormSet(['fund_code' => '510']);

    assertDatabaseHas(Fund::class, ['code' => '510', 'name' => 'Water Utility']);
});

it('quick-creates a division inline from the division select, scoped to the chosen department', function () {
    $department = Department::factory()->create();

    livewire(CreateGlCode::class)
        ->fillForm(['department_code' => $department->code])
        ->callFormComponentAction('division_id', 'createOption', data: [
            'code' => '117',
            'name' => 'Network Services',
            'active' => true,
        ]);

    $division = Division::where(['department_code' => $department->code, 'code' => '117'])->firstOrFail();

    assertDatabaseHas(Division::class, [
        'id' => $division->id,
        'department_code' => $department->code,
        'code' => '117',
    ]);
});

it('reuses an existing division instead of duplicating it when quick-creating a duplicate code', function () {
    $department = Department::factory()->create();
    $existing = Division::factory()->create(['department_code' => $department->code, 'code' => '117']);

    livewire(CreateGlCode::class)
        ->fillForm(['department_code' => $department->code])
        ->callFormComponentAction('division_id', 'createOption', data: [
            'code' => '117',
            'name' => 'Duplicate Attempt',
            'active' => true,
        ]);

    expect(Division::where(['department_code' => $department->code, 'code' => '117'])->count())->toBe(1);
    expect(Division::find($existing->id)->name)->toBe($existing->name);
});
