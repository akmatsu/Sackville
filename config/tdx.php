<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hardware Model Sync Schedule
    |--------------------------------------------------------------------------
    |
    | Default frequency for the TDX hardware model sync job. These values only
    | seed the initial `sync_schedules` row (see the accompanying migration)
    | and act as a fallback if that table isn't available yet — after seeding,
    | the schedule is configured from the admin panel (Sync Runs page) and
    | stored in the database, not here.
    |
    */

    'hardware_sync' => [
        'frequency' => env('TDX_HARDWARE_SYNC_FREQUENCY', 'daily'),
        'time_of_day' => env('TDX_HARDWARE_SYNC_TIME', '23:00'),
        'interval_hours' => env('TDX_HARDWARE_SYNC_INTERVAL_HOURS'),
        'timezone' => env('TDX_HARDWARE_SYNC_TIMEZONE', 'America/Anchorage'),
    ],

];
