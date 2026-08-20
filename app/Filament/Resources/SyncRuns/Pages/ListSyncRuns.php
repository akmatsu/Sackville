<?php

namespace App\Filament\Resources\SyncRuns\Pages;

use App\Enums\SyncFrequency;
use App\Enums\SyncRunStatus;
use App\Filament\Resources\SyncRuns\SyncRunResource;
use App\Jobs\SyncTdxHardwareModels;
use App\Jobs\SyncTdxMetronet;
use App\Jobs\SyncTdxMobileDevices;
use App\Jobs\SyncTdxPublicWifi;
use App\Models\SyncRun;
use App\Models\SyncSchedule;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ListSyncRuns extends ListRecords
{
    protected static string $resource = SyncRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->integrationActionGroup(
                'Hardware',
                $this->scheduleAction('configureSchedule', 'tdx', 'Configure schedule', 'hardware_sync'),
                $this->syncNowAction('syncHardwareModels', 'tdx', 'Sync now', SyncTdxHardwareModels::class, 'Hardware model sync queued'),
            ),
            $this->integrationActionGroup(
                'Mobile',
                $this->scheduleAction('configureMobileSchedule', 'tdx-mobile', 'Configure schedule', 'mobile_sync'),
                $this->syncNowAction('syncMobileDevices', 'tdx-mobile', 'Sync now', SyncTdxMobileDevices::class, 'Mobile device sync queued'),
            ),
            $this->integrationActionGroup(
                'Public Wifi',
                $this->scheduleAction('configurePublicWifiSchedule', 'tdx-public-wifi', 'Configure schedule', 'public_wifi_sync'),
                $this->syncNowAction('syncPublicWifi', 'tdx-public-wifi', 'Sync now', SyncTdxPublicWifi::class, 'Public wifi sync queued'),
            ),
            $this->integrationActionGroup(
                'Metronet',
                $this->scheduleAction('configureMetronetSchedule', 'tdx-metronet', 'Configure schedule', 'metronet_sync'),
                $this->syncNowAction('syncMetronet', 'tdx-metronet', 'Sync now', SyncTdxMetronet::class, 'Metronet sync queued'),
            ),
        ];
    }

    /**
     * Collapses one integration's "Configure schedule" and "Sync now"
     * actions into a single labeled dropdown, so the header doesn't grow a
     * new pair of buttons for every TDX integration added.
     */
    protected function integrationActionGroup(string $label, Action $scheduleAction, Action $syncNowAction): ActionGroup
    {
        return ActionGroup::make([$scheduleAction, $syncNowAction])
            ->label($label)
            ->button()
            ->color('gray');
    }

    /**
     * Builds the "Configure ... sync schedule" action for one integration.
     * $configSection is the config/tdx.php key (e.g. "mobile_sync") used as
     * the timezone fallback when saving, since the form itself only edits
     * frequency/time/interval.
     */
    protected function scheduleAction(string $name, string $integration, string $label, string $configSection): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon(Heroicon::OutlinedClock)
            ->fillForm(function () use ($integration): array {
                $schedule = SyncSchedule::forIntegration($integration);

                return [
                    'frequency' => $schedule->frequency,
                    'time_of_day' => $schedule->time_of_day,
                    'interval_hours' => $schedule->interval_hours,
                ];
            })
            ->schema($this->scheduleFormSchema())
            ->action(function (array $data) use ($integration, $configSection): void {
                SyncSchedule::query()->updateOrCreate(
                    ['integration' => $integration],
                    [
                        'frequency' => $data['frequency'],
                        'time_of_day' => $data['frequency'] === SyncFrequency::Daily
                            ? $data['time_of_day']
                            : null,
                        'interval_hours' => $data['frequency'] === SyncFrequency::EveryNHours
                            ? $data['interval_hours']
                            : null,
                        'timezone' => config("tdx.{$configSection}.timezone"),
                    ],
                );

                Notification::make()
                    ->title('Sync schedule updated')
                    ->success()
                    ->send();
            });
    }

    /**
     * Builds the "Sync ... now" action for one integration, pre-creating a
     * Running SyncRun so it shows in the table immediately, before the
     * queue worker picks the job up.
     *
     * @param  class-string<SyncTdxHardwareModels|SyncTdxMobileDevices|SyncTdxPublicWifi|SyncTdxMetronet>  $jobClass
     */
    protected function syncNowAction(string $name, string $integration, string $label, string $jobClass, string $queuedMessage): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon(Heroicon::OutlinedArrowPath)
            ->requiresConfirmation()
            ->action(function () use ($integration, $jobClass, $queuedMessage): void {
                $syncRun = SyncRun::create([
                    'integration' => $integration,
                    'started_at' => now(),
                    'status' => SyncRunStatus::Running,
                ]);

                $jobClass::dispatch($syncRun->id);

                Notification::make()
                    ->title($queuedMessage)
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, Component>
     */
    protected function scheduleFormSchema(): array
    {
        return [
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
        ];
    }
}
