<?php

use App\Enums\Status;
use App\Models\Grievance;
use App\Support\WorkingDays;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ghm:recount-working-days', function () {
    // Quietly: a system job, not a staff action, so no observer or activity entry.
    Grievance::whereIn('status', Status::open())->lazyById()->each(function (Grievance $grievance): void {
        $grievance->working_days_open = WorkingDays::between($grievance->received_at, now());
        $grievance->saveQuietly();
    });
})->purpose('Recount working days open for open cases (A2)');

Schedule::command('ghm:recount-working-days')->dailyAt('00:30');
