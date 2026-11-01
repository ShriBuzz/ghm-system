<?php

use App\Enums\Status;
use App\Models\Grievance;
use App\Models\Holiday;
use App\Support\WorkingDays;
use Illuminate\Support\Carbon;

it('counts the days after receipt, skipping Saturdays and holidays', function () {
    $friday = Carbon::parse('2026-11-20 10:00');
    $monday = Carbon::parse('2026-11-23 09:00');

    expect(WorkingDays::between($friday, $friday->copy()->setTime(17, 0)))->toBe(0)
        ->and(WorkingDays::between($friday, $monday))->toBe(2);

    Holiday::forceCreate(['date' => '2026-11-22', 'name' => 'Test holiday']);

    expect(WorkingDays::between($friday, $monday))->toBe(1);
})->group('A2');

it('changes day at midnight in Kathmandu, not UTC', function () {
    $lateThursday = Carbon::parse('2026-11-19 23:50');
    $earlyFridayUtc = Carbon::parse('2026-11-19 18:30', 'UTC'); // 00:15 Friday in Kathmandu

    expect(WorkingDays::between($lateThursday, $earlyFridayUtc))->toBe(1);
})->group('A2');

it('stops the clock at Resolved, Referred or Closed and sets the closed date', function () {
    $this->travelTo('2026-11-20 10:00');
    $case = Grievance::factory()->create();

    $this->travelTo('2026-11-23 10:00');
    $case->forceFill(['status' => Status::Resolved])->save();
    expect($case->working_days_open)->toBe(2)->and($case->closed_at)->toBeNull();

    $this->travelTo('2026-12-04 10:00');
    $case->forceFill(['status' => Status::Closed])->save();
    expect($case->working_days_open)->toBe(2)
        ->and($case->closed_at->toDateString())->toBe('2026-12-04');

    $case->forceFill(['status' => Status::Reopened])->save();
    expect($case->closed_at)->toBeNull()->and($case->working_days_open)->toBe(12);
})->group('A2');

it('clears the closed date whenever a case leaves Closed, not only at Reopened', function (Status $next) {
    $case = Grievance::factory()->create();
    $case->forceFill(['status' => Status::Closed])->save();

    $case->forceFill(['status' => $next])->save();

    expect($case->closed_at)->toBeNull();
})->with([Status::UnderInvestigation, Status::Resolved])->group('A2', 'A4');

it('counts an offline case from the day it was received', function () {
    $this->travelTo('2026-11-23 10:00');

    expect(Grievance::factory()->create(['received_at' => '2026-11-20 09:00'])->working_days_open)->toBe(2);
})->group('A2', 'A6');

it('recounts open cases every night and leaves stopped ones alone', function () {
    $this->travelTo('2026-11-20 10:00');
    $open = Grievance::factory()->create();
    $resolved = Grievance::factory()->create();
    $resolved->forceFill(['status' => Status::Resolved])->save();

    $this->travelTo('2026-11-23 00:30');
    $this->artisan('ghm:recount-working-days')->assertSuccessful();

    expect($open->fresh()->working_days_open)->toBe(2)
        ->and($resolved->fresh()->working_days_open)->toBe(0);
})->group('A2');

it('schedules the recount at 00:30', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('ghm:recount-working-days');
})->group('A2');
