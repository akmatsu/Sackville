<?php

namespace App\Filament\Resources\SyncRuns\Pages;

use App\Enums\SyncFrequency;
use App\Enums\SyncRunStatus;
use App\Filament\Resources\SyncRuns\SyncRunResource;
use App\Jobs\SyncTdxHardwareModels;
use App\Jobs\SyncTdxMobileDevices;
use App\Models\SyncRun;
use App\Models\SyncSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ListSyncRuns extends ListRecords
{
    protected static string $resource = SyncRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configureSchedule')
                ->label('Configure sync schedule')
                ->icon(Heroicon::OutlinedClock)
                ->fillForm(function (): array {
                    $schedule = SyncSchedule::forIntegration('tdx');

                    return [
                        'frequency' => $schedule->frequency,
                        'time_of_day' => $schedule->time_of_day,
                        'interval_hours' => $schedule->interval_hours,
                    ];
                })
                ->schema([
                    Select::make('frequency')
                        ->options(SyncFrequency::class)
                        ->required()
                        ->live(),
                    TimePicker::make('time_of_day')
                        ->label('Time')
                        ->seconds(false)
                        ->format('H:i')
                        ->required(fn (Get $get): bool => $get('frequency') === SyncFrequency::Daily)
                        ->visible(fn (Get $get): bool => $get('frequency') === SyncFrequency::Daily),
                    Select::make('interval_hours')
                        ->label('Interval')
                        ->options([
                            1 => 'Every hour',
                            2 => 'Every 2 hours',
                            3 => 'Every 3 hours',
                            4 => 'Every 4 hours',
                            6 => 'Every 6 hours',
                            8 => 'Every 8 hours',
                            12 => 'Every 12 hours',
                        ])
                        ->required(fn (Get $get): bool => $get('frequency') === SyncFrequency::EveryNHours)
                        ->visible(fn (Get $get): bool => $get('frequency') === SyncFrequency::EveryNHours),
                ])
                ->action(function (array $data): void {
                    SyncSchedule::query()->updateOrCreate(
                        ['integration' => 'tdx'],
                        [
                            'frequency' => $data['frequency'],
                            'time_of_day' => $data['frequency'] === SyncFrequency::Daily
                                ? $data['time_of_day']
                                : null,
                            'interval_hours' => $data['frequency'] === SyncFrequency::EveryNHours
                                ? $data['interval_hours']
                                : null,
                            'timezone' => config('tdx.hardware_sync.timezone'),
                        ],
                    );

                    Notification::make()
                        ->title('Sync schedule updated')
                        ->success()
                        ->send();
                }),
            Action::make('syncHardwareModels')
                ->label('Sync hardware models now')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->action(function (): void {
                    $syncRun = SyncRun::create([
                        'integration' => 'tdx',
                        'started_at' => now(),
                        'status' => SyncRunStatus::Running,
                    ]);

                    SyncTdxHardwareModels::dispatch($syncRun->id);

                    Notification::make()
                        ->title('Hardware model sync queued')
                        ->success()
                        ->send();
                }),
            Action::make('configureMobileSchedule')
                ->label('Configure mobile sync schedule')
                ->icon(Heroicon::OutlinedClock)
                ->fillForm(function (): array {
                    $schedule = SyncSchedule::forIntegration('tdx-mobile');

                    return [
                        'frequency' => $schedule->frequency,
                        'time_of_day' => $schedule->time_of_day,
                        'interval_hours' => $schedule->interval_hours,
                    ];
                })
                ->schema([
                    Select::make('frequency')
                        ->options(SyncFrequency::class)
                        ->required()
                        ->live(),
                    TimePicker::make('time_of_day')
                        ->label('Time')
                        ->seconds(false)
                        ->format('H:i')
                        ->required(fn (Get $get): bool => $get('frequency') === SyncFrequency::Daily)
                        ->visible(fn (Get $get): bool => $get('frequency') === SyncFrequency::Daily),
                    Select::make('interval_hours')
                        ->label('Interval')
                        ->options([
                            1 => 'Every hour',
                            2 => 'Every 2 hours',
                            3 => 'Every 3 hours',
                            4 => 'Every 4 hours',
                            6 => 'Every 6 hours',
                            8 => 'Every 8 hours',
                            12 => 'Every 12 hours',
                        ])
                        ->required(fn (Get $get): bool => $get('frequency') === SyncFrequency::EveryNHours)
                        ->visible(fn (Get $get): bool => $get('frequency') === SyncFrequency::EveryNHours),
                ])
                ->action(function (array $data): void {
                    SyncSchedule::query()->updateOrCreate(
                        ['integration' => 'tdx-mobile'],
                        [
                            'frequency' => $data['frequency'],
                            'time_of_day' => $data['frequency'] === SyncFrequency::Daily
                                ? $data['time_of_day']
                                : null,
                            'interval_hours' => $data['frequency'] === SyncFrequency::EveryNHours
                                ? $data['interval_hours']
                                : null,
                            'timezone' => config('tdx.mobile_sync.timezone'),
                        ],
                    );

                    Notification::make()
                        ->title('Sync schedule updated')
                        ->success()
                        ->send();
                }),
            Action::make('syncMobileDevices')
                ->label('Sync mobile devices now')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->action(function (): void {
                    $syncRun = SyncRun::create([
                        'integration' => 'tdx-mobile',
                        'started_at' => now(),
                        'status' => SyncRunStatus::Running,
                    ]);

                    SyncTdxMobileDevices::dispatch($syncRun->id);

                    Notification::make()
                        ->title('Mobile device sync queued')
                        ->success()
                        ->send();
                }),
        ];
    }
}
