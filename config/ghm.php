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

];
