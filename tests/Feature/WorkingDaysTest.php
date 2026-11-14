<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Resources\Grievances\Pages\ViewGrievance;
use App\Models\Grievance;
use App\Models\Holiday;
use App\Support\WorkingDays;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

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

    // A reopened case counts again from the reopening date.
    $case->forceFill(['status' => Status::Reopened])->save();
    expect($case->closed_at)->toBeNull()->and($case->working_days_open)->toBe(0);
})->group('A2', 'U3');

it('restarts the count from the reopening date, each time a stopped case is reopened', function () {
    $this->travelTo('2026-11-20 10:00');
    $case = Grievance::factory()->create();

    $this->travelTo('2026-11-23 10:00');
    $case->forceFill(['status' => Status::Resolved])->save();

    $this->travelTo('2026-12-04 10:00');
    $case->forceFill(['status' => Status::Reopened])->save();
    expect($case->reopened_at->toDateString())->toBe('2026-12-04')->and($case->working_days_open)->toBe(0);

    // The nightly recount and the next status change count from the reopening too (Saturday 5 Dec skipped).
    $this->travelTo('2026-12-08 00:30');
    $this->artisan('ghm:recount-working-days')->assertSuccessful();
    expect($case->fresh()->working_days_open)->toBe(3);

    $this->travelTo('2026-12-09 10:00');
    $case->refresh()->forceFill(['status' => Status::UnderInvestigation])->save();
    expect($case->working_days_open)->toBe(4);

    $case->forceFill(['status' => Status::Closed])->save();
    $this->travelTo('2026-12-14 10:00');
    $case->forceFill(['status' => Status::Reopened])->save();
    expect($case->reopened_at->toDateString())->toBe('2026-12-14')->and($case->working_days_open)->toBe(0);
})->group('A2', 'U3');

it('freezes the count in every stopped status and restarts it on any move back to an open one', function (Status $stopped, Status $open) {
    $this->travelTo('2026-11-20 10:00');
    $case = Grievance::factory()->create();

    $this->travelTo('2026-11-23 10:00');
    $case->forceFill(['status' => $stopped, 'escalated_at' => now()])->save();

    $this->travelTo('2026-12-04 10:00');
    $this->artisan('ghm:recount-working-days')->assertSuccessful();
    expect($case->refresh()->working_days_open)->toBe(2);

    $case->forceFill(['status' => $open])->save();

    expect($case->reopened_at->toDateString())->toBe('2026-12-04')
        ->and($case->working_days_open)->toBe(0)
        ->and($case->escalated_at)->toBeNull();
})->with([Status::Resolved, Status::Closed, Status::ReferredOutOfScope])
    ->with([Status::UnderAssessment, Status::UnderInvestigation, Status::Reopened])
    ->group('A2', 'U3', 'U6');

it('keeps counting from receipt when an open case is set to Reopened, so an overdue case stays overdue', function () {
    $this->travelTo('2026-11-20 10:00');
    $case = Grievance::factory()->create();
    $case->forceFill(['status' => Status::UnderInvestigation])->save();

    $this->travelTo('2026-11-24 10:00');
    $case->forceFill(['status' => Status::Reopened])->save();

    expect($case->reopened_at)->toBeNull()->and($case->working_days_open)->toBe(3);
})->group('A2', 'U3');

it('shows on the case page when the count restarted', function () {
    app()->setLocale('en');
    signedInAs(Role::Administrator);
    $this->travelTo('2026-12-04 10:00');
    $case = Grievance::factory()->create(['received_at' => '2026-11-20 10:00']);
    $case->forceFill(['status' => Status::Resolved])->save();
    $case->forceFill(['status' => Status::Reopened])->save();

    Livewire::test(ViewGrievance::class, ['record' => $case->id])
        ->assertSee('Reopened on')->assertSee('2083-08-18 (2026-12-04) 10:00');
})->group('A2', 'U3');

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
