<?php

use Carbon\CarbonInterface;

return [

    /*
    |--------------------------------------------------------------------------
    | Grievance Handling Rules
    |--------------------------------------------------------------------------
    |
    | Fixed values the grievance workflow reads instead of repeating them in
    | code. The upload limit is in kilobytes, the unit Laravel's file
    | validation rules use.
    |
    */

    'sla_days' => 15,

    'weekend_days' => [CarbonInterface::SATURDAY],

    'pin_length' => 6,

    'upload_max_kb' => 5 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Public form and status page limits
    |--------------------------------------------------------------------------
    |
    | Per visitor key (App\Support\VisitorKey, never the IP itself). Factory
    | Wi-Fi and phone carriers put many people behind one address, so the
    | submission limit is generous. The Playwright test server raises them.
    |
    */

    'public' => [
        'submissions_per_hour' => (int) env('GHM_SUBMISSIONS_PER_HOUR', 10),
        'failed_lookups_per_hour' => (int) env('GHM_FAILED_LOOKUPS_PER_HOUR', 10),
        'min_fill_seconds' => (int) env('GHM_MIN_FILL_SECONDS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | First Administrator
    |--------------------------------------------------------------------------
    |
    | AdministratorSeeder creates this account and refuses to run while any
    | value is empty. Set them in ".env" only; they never go into the repo.
    |
    */

    'admin' => [
        'name' => env('GHM_ADMIN_NAME'),
        'email' => env('GHM_ADMIN_EMAIL'),
        'password' => env('GHM_ADMIN_PASSWORD'),
    ],

];
