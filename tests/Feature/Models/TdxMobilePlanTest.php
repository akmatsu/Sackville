<?php

use App\Models\TdxAsset;
use App\Models\TdxMobilePlan;

it('resolves devices() by matching a device\'s plan_serial against this plan\'s serial', function () {
    $plan = TdxMobilePlan::factory()->create(['serial' => '9073550563']);
    $device = TdxAsset::factory()->create(['plan_serial' => '9073550563']);
    TdxAsset::factory()->create(['plan_serial' => 'some-other-serial']);

    expect($plan->devices)->toHaveCount(1);
    expect($plan->devices->first()->id)->toBe($device->id);
});

it('resolves plan() on the device by matching plan_serial against the plan\'s serial', function () {
    $plan = TdxMobilePlan::factory()->create(['serial' => '9073550563']);
    $device = TdxAsset::factory()->create(['plan_serial' => '9073550563']);

    expect($device->plan)->not->toBeNull();
    expect($device->plan->id)->toBe($plan->id);
});

it('returns no plan when plan_serial is null', function () {
    $device = TdxAsset::factory()->create(['plan_serial' => null]);

    expect($device->plan)->toBeNull();
});
