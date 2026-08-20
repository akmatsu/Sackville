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

    /*
    |--------------------------------------------------------------------------
    | Mobile Device Sync Schedule
    |--------------------------------------------------------------------------
    |
    | Same as above, but for the mobile device sync job. Independent cadence
    | from the hardware model sync so each can be tuned separately.
    |
    */

    'mobile_sync' => [
        'frequency' => env('TDX_MOBILE_SYNC_FREQUENCY', 'daily'),
        'time_of_day' => env('TDX_MOBILE_SYNC_TIME', '23:30'),
        'interval_hours' => env('TDX_MOBILE_SYNC_INTERVAL_HOURS'),
        'timezone' => env('TDX_MOBILE_SYNC_TIMEZONE', 'America/Anchorage'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Wifi Sync Schedule
    |--------------------------------------------------------------------------
    |
    | Same as above, but for the public wifi circuit sync job. Independent
    | cadence from the other syncs so each can be tuned separately.
    |
    */

    'public_wifi_sync' => [
        'frequency' => env('TDX_PUBLIC_WIFI_SYNC_FREQUENCY', 'daily'),
        'time_of_day' => env('TDX_PUBLIC_WIFI_SYNC_TIME', '00:00'),
        'interval_hours' => env('TDX_PUBLIC_WIFI_SYNC_INTERVAL_HOURS'),
        'timezone' => env('TDX_PUBLIC_WIFI_SYNC_TIMEZONE', 'America/Anchorage'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metronet Sync Schedule
    |--------------------------------------------------------------------------
    |
    | Same as above, but for the Metronet circuit sync job. Independent
    | cadence from the other syncs so each can be tuned separately.
    |
    */

    'metronet_sync' => [
        'frequency' => env('TDX_METRONET_SYNC_FREQUENCY', 'daily'),
        'time_of_day' => env('TDX_METRONET_SYNC_TIME', '00:30'),
        'interval_hours' => env('TDX_METRONET_SYNC_INTERVAL_HOURS'),
        'timezone' => env('TDX_METRONET_SYNC_TIMEZONE', 'America/Anchorage'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Connection
    |--------------------------------------------------------------------------
    |
    | Credentials for authenticating against the TDX Web API. See
    | App\Support\Tdx\TdxClient for how these are used.
    |
    */

    'api' => [
        'base_url' => env('TDX_BASE_URL', 'https://support.matsu.gov/TDWebApi/api'),
        'username' => env('TDX_USERNAME'),
        'password' => env('TDX_PASSWORD'),
    ],

];
