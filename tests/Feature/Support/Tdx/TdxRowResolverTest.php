<?php

use App\Enums\ObjectCodeCategory;
use App\Models\Department;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\Vendor;
use App\Support\Tdx\TdxRowResolver;

beforeEach(function () {
    $this->resolver = new TdxRowResolver;
});

it('parses a well-formed IT Funding field into its department/division/location/GL parts', function () {
    $funding = $this->resolver->parseFundingField('Community Development - Library - Willow -  200.170.507');

    expect($funding)->toBe([
        'department_name' => 'Community Development',
        'division_name' => 'Library',
        'location_name' => 'Willow',
        'fund_code' => '200',
        'department_code' => '170',
        'division_code' => '507',
    ]);
});

it('returns null for a funding field with too few segments', function () {
    expect($this->resolver->parseFundingField('Not a parseable funding string'))->toBeNull();
});

it('returns null for a funding field whose GL segment cannot be split into three parts', function () {
    expect($this->resolver->parseFundingField('Public Works - Project Mgmt -  100.115'))->toBeNull();
});

it('returns null funding for a null or empty value', function () {
    expect($this->resolver->parseFundingField(null))->toBeNull();
    expect($this->resolver->parseFundingField(''))->toBeNull();
});

it('resolves responsible org fields to null when funding is null', function () {
    expect($this->resolver->resolveResponsibleOrg(null))->toBe([
        'department_code' => null,
        'responsible_division_id' => null,
        'responsible_location_id' => null,
    ]);
});

it('auto-creates a responsible division and location from parsed funding', function () {
    $funding = $this->resolver->parseFundingField('Community Development - Library - Willow -  200.170.507');

    $org = $this->resolver->resolveResponsibleOrg($funding);

    expect($org['department_code'])->toBe('Community Development');

    $division = ResponsibleDivision::findOrFail($org['responsible_division_id']);
    expect($division->name)->toBe('Library');
    expect($division->active)->toBeTrue();

    $location = ResponsibleLocation::findOrFail($org['responsible_location_id']);
    expect($location->name)->toBe('Willow');
});

it('resolves a GL code for arbitrary object/sub-object codes, building a generic label from the sub-object name', function () {
    $glCode = $this->resolver->resolveGlCode(
        '100', '115', '122',
        '421', 'Communications', ObjectCodeCategory::Contractual,
        '100', 'Mobile Service Plans',
    );

    expect($glCode->code_string)->toBe('100.115.122.421.100');
    expect($glCode->label)->toBe('Division 122 (auto-created) — Mobile Service Plans');

    $fund = Fund::findOrFail('100');
    expect($fund->name)->toBe('General Fund');

    $department = Department::findOrFail('115');
    expect($department->name)->toBe('Information Technology');
});

it('reuses the same GL code for two different object/sub-object calls that resolve the same combination', function () {
    $first = $this->resolver->resolveGlCode(
        '100', '115', '122',
        '434', 'Equipment', ObjectCodeCategory::Equipment,
        '000', 'IT Equipment under $25,000',
    );
    $second = $this->resolver->resolveGlCode(
        '100', '115', '122',
        '434', 'Equipment', ObjectCodeCategory::Equipment,
        '000', 'IT Equipment under $25,000',
    );

    expect($second->id)->toBe($first->id);
    expect(GlCode::count())->toBe(1);
});

it('resolves a hardware model under the given category name, auto-creating vendor and category', function () {
    $model = $this->resolver->resolveHardwareModel([
        'AssetID' => 1,
        'ManufacturerName' => 'Apple',
        'ProductModelName' => 'iPhone XR',
    ], 'Mobile');

    expect($model)->not->toBeNull();
    expect(Vendor::where('name', 'Apple')->exists())->toBeTrue();
    expect(HardwareCategory::where('name', 'Mobile')->exists())->toBeTrue();
    expect($model->name)->toBe('iPhone XR');
});

it('returns null from resolveHardwareModel when manufacturer or model is missing', function () {
    $model = $this->resolver->resolveHardwareModel(['AssetID' => 1, 'ManufacturerName' => '', 'ProductModelName' => ''], 'Mobile');

    expect($model)->toBeNull();
});

it('parses FY replacement digits out of a string like "FY30"', function () {
    expect($this->resolver->parseFyReplacement('FY30'))->toBe(30);
    expect($this->resolver->parseFyReplacement(null))->toBeNull();
    expect($this->resolver->parseFyReplacement(''))->toBeNull();
});

it('trims strings to null via stringOrNull', function () {
    expect($this->resolver->stringOrNull('  hello  '))->toBe('hello');
    expect($this->resolver->stringOrNull(''))->toBeNull();
    expect($this->resolver->stringOrNull(null))->toBeNull();
    expect($this->resolver->stringOrNull(123))->toBeNull();
});
