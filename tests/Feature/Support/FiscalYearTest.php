<?php

use App\Support\FiscalYear;

use function Pest\Laravel\travelTo;

it('resolves a december date to the fiscal year that started the previous july', function () {
    travelTo('2026-12-01');

    expect(FiscalYear::current())->toBe(27);
});

it('resolves a march date to the fiscal year that started the previous july', function () {
    travelTo('2027-03-01');

    expect(FiscalYear::current())->toBe(27);
});

it('rolls over to the new fiscal year on july 1st', function () {
    travelTo('2026-07-01');

    expect(FiscalYear::current())->toBe(27);
});

it('is still the prior fiscal year on june 30th', function () {
    travelTo('2026-06-30');

    expect(FiscalYear::current())->toBe(26);
});
