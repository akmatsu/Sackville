<?php

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Models\ActivityLog;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists activity log entries', function () {
    $entries = ActivityLog::factory()->count(3)->create();

    livewire(ListActivityLogs::class)
        ->assertCanSeeTableRecords($entries);
});

it('views an activity log entry', function () {
    $entry = ActivityLog::factory()->create();

    livewire(ViewActivityLog::class, ['record' => $entry->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'table_name' => $entry->table_name,
            'record_id' => $entry->record_id,
        ]);
});

it('does not register create or edit routes for activity log entries', function () {
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.activity-logs.create'))->toBeFalse();
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.activity-logs.edit'))->toBeFalse();
});
