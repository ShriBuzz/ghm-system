<?php

use App\Enums\AlertKind;
use App\Enums\MessageDirection;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\Status;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\User;
use App\Notifications\CaseAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Notification::fake();

    $this->admin = User::factory()->create(['role' => Role::Administrator]);
    $this->focal = User::factory()->create(['role' => Role::FocalPerson]);
    $this->officer = User::factory()->create(['role' => Role::RestrictedOfficer]);
    $this->inactive = User::factory()->create(['role' => Role::FocalPerson, 'is_active' => false]);
});

it('alerts active focal persons, not the Administrator, about a new case', function () {
    Grievance::factory()->create();

    Notification::assertSentTo($this->focal, CaseAlert::class);
    Notification::assertNotSentTo([$this->admin, $this->officer, $this->inactive], CaseAlert::class);
})->group('N1');

it('alerts only restricted-case officers about a restricted case', function () {
    Grievance::factory()->create(['category_id' => Category::factory()->create(['is_restricted' => true])]);
    Grievance::factory()->create(['manually_restricted' => true]);

    Notification::assertSentToTimes($this->officer, CaseAlert::class, 2);
    Notification::assertNotSentTo([$this->admin, $this->focal, $this->inactive], CaseAlert::class);
})->group('N1');

it('does not alert the staff member who entered an offline case', function () {
    $other = User::factory()->create(['role' => Role::FocalPerson]);

    Grievance::factory()->create(['created_by' => $this->focal->id]);

    Notification::assertSentTo($other, CaseAlert::class);
    Notification::assertNotSentTo($this->focal, CaseAlert::class);
})->group('N1');

it('alerts about each complainant message, not about staff replies', function () {
    $case = Grievance::factory()->create(['created_by' => $this->focal->id]);

    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Staff, 'author_id' => $this->focal->id]);
    Notification::assertNothingSentTo($this->focal);

    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Complainant]);
    Notification::assertSentToTimes($this->focal, CaseAlert::class, 1);
})->group('N1');

it('sends a queued English email with no case details, only a sign-in link, for every kind of alert', function () {
    $case = Grievance::factory()->create(['description' => 'Wages unpaid for three months']);
    $subjects = [];

    foreach (AlertKind::cases() as $kind) {
        $alert = new CaseAlert($case->id, $kind);
        $mail = $alert->toMail($this->focal);
        $text = implode(' ', [$mail->subject, ...$mail->introLines, ...$mail->outroLines]);
        $subjects[] = $mail->subject;

        expect($alert)->toBeInstanceOf(ShouldQueue::class)
            ->and($alert->locale)->toBe('en')
            ->and($mail->actionUrl)->toBe(url("/admin/grievances/{$case->id}"))
            ->and($mail->actionText)->toBe('Log in to view')
            ->and($text)
            ->not->toContain($case->reference)
            ->not->toContain($case->category->name_en)
            ->not->toContain((string) $case->name)
            ->not->toContain('Wages unpaid')
            // Laravel's mail header loads a logo from laravel.com while the app is named "Laravel".
            ->and((string) $mail->render())->not->toContain('laravel.com');
    }

    expect(array_unique($subjects))->toHaveCount(count(AlertKind::cases()));
})->group('N1', 'U6');

/**
 * How many alerts of one kind a user was sent.
 */
function alertsOf(User $user, AlertKind $kind): int
{
    return Notification::sent($user, CaseAlert::class, fn (CaseAlert $alert): bool => $alert->kind === $kind)->count();
}

it('alerts only the active assignee about a complainant message, and everyone who can see the case when there is none', function () {
    $other = User::factory()->create(['role' => Role::FocalPerson]);

    $assigned = Grievance::factory()->create(['assigned_to' => $this->focal->id]);
    GrievanceMessage::factory()->for($assigned)->create(['direction' => MessageDirection::Complainant]);

    expect([alertsOf($this->focal, AlertKind::Message), alertsOf($other, AlertKind::Message), alertsOf($this->admin, AlertKind::Message)])->toBe([1, 0, 0]);

    // An assignee who left gets nothing; the others hear about it as about a new case.
    $left = Grievance::factory()->create(['assigned_to' => $this->inactive->id]);
    GrievanceMessage::factory()->for($left)->create(['direction' => MessageDirection::Complainant]);

    expect([alertsOf($this->focal, AlertKind::Message), alertsOf($other, AlertKind::Message), alertsOf($this->inactive, AlertKind::Message)])->toBe([2, 1, 0]);
})->group('U6', 'N1');

it('alerts the Administrator about a message on a case assigned to them', function () {
    $case = Grievance::factory()->create(['assigned_to' => $this->admin->id]);
    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Complainant]);

    expect([alertsOf($this->admin, AlertKind::Message), alertsOf($this->focal, AlertKind::Message)])->toBe([1, 0]);
})->group('U6', 'N1');

it('alerts the Administrators about a restricted case and its messages while no restricted-case officer is active', function () {
    $this->officer->update(['is_active' => false]);

    $case = Grievance::factory()->create(['manually_restricted' => true]);
    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Complainant]);

    expect([alertsOf($this->admin, AlertKind::NewCase), alertsOf($this->admin, AlertKind::Message)])->toBe([1, 1])
        ->and(Notification::sent($this->focal, CaseAlert::class))->toBeEmpty();

    // An active officer who entered the case knows of it: nobody else is told.
    $this->officer->update(['is_active' => true]);
    Grievance::factory()->create(['manually_restricted' => true, 'created_by' => $this->officer->id]);

    expect(alertsOf($this->admin, AlertKind::NewCase))->toBe(1)
        ->and(Notification::sent($this->officer, CaseAlert::class))->toBeEmpty();
})->group('U6', 'N1');

it('alerts the Administrators about a case and its messages while no focal person is active', function () {
    // The only focal person is on leave and deactivated: otherwise nobody would hear of the case.
    $this->focal->update(['is_active' => false]);

    $case = Grievance::factory()->create();
    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Complainant]);

    expect([alertsOf($this->admin, AlertKind::NewCase), alertsOf($this->admin, AlertKind::Message)])->toBe([1, 1])
        ->and(Notification::sent($this->officer, CaseAlert::class))->toBeEmpty();
})->group('U6', 'N1');

it('alerts the restricted-case officers when a case is marked restricted, or the Administrators while none is active', function () {
    $case = Grievance::factory()->create();
    $this->actingAs($this->focal);
    $case->update(['manually_restricted' => true]);

    expect([alertsOf($this->officer, AlertKind::Restricted), alertsOf($this->admin, AlertKind::Restricted), alertsOf($this->focal, AlertKind::Restricted)])
        ->toBe([1, 0, 0]);

    // With no active officer, the Administrators, but not the one who marked it.
    $this->officer->update(['is_active' => false]);
    $second = User::factory()->create(['role' => Role::Administrator]);
    $this->actingAs($this->admin);
    Grievance::factory()->create()->update(['manually_restricted' => true]);

    expect([alertsOf($second, AlertKind::Restricted), alertsOf($this->admin, AlertKind::Restricted)])->toBe([1, 0]);

    // Already restricted by its category: marking it changes nothing anyone can see, so no alert.
    Grievance::factory()->create(['category_id' => Category::factory()->create(['is_restricted' => true])])->update(['manually_restricted' => true]);

    expect(alertsOf($second, AlertKind::Restricted))->toBe(1);
})->group('U6', 'N1', 'A5');

it('alerts the other active Administrators when a case is set to Critical, each time it becomes Critical', function () {
    $second = User::factory()->create(['role' => Role::Administrator]);
    $case = Grievance::factory()->create(['priority' => Priority::Low]);
    $this->actingAs($this->admin);

    $case->update(['priority' => Priority::High]);
    $case->update(['priority' => Priority::Critical]);
    $case->update(['status' => Status::UnderAssessment]); // Still Critical: no new alert.

    expect([alertsOf($second, AlertKind::Critical), alertsOf($this->admin, AlertKind::Critical), alertsOf($this->focal, AlertKind::Critical)])->toBe([1, 0, 0]);

    $case->update(['priority' => Priority::High]);
    $case->update(['priority' => Priority::Critical]);

    expect(alertsOf($second, AlertKind::Critical))->toBe(2);
})->group('U6');

it('alerts the Administrators once when an open case passes its target, logs it, and again after a reopening', function () {
    $this->travelTo('2026-11-20 10:00');
    $late = Grievance::factory()->create(['received_at' => now()->subDays(30), 'status' => Status::UnderInvestigation]);
    $fresh = Grievance::factory()->create(['received_at' => now()->subDays(2)]);
    $done = Grievance::factory()->create(['received_at' => now()->subDays(30), 'status' => Status::Resolved]);

    $this->artisan('ghm:recount-working-days')->assertSuccessful();
    $this->artisan('ghm:recount-working-days')->assertSuccessful();

    $escalated = Activity::where('event', 'escalated')->get();
    expect(alertsOf($this->admin, AlertKind::PastTarget))->toBe(1)
        ->and(alertsOf($this->focal, AlertKind::PastTarget))->toBe(0)
        ->and(Notification::sent($this->admin, CaseAlert::class)->first()->grievanceId)->toBe($late->id)
        ->and($late->fresh()->escalated_at?->toDateTimeString())->toBe('2026-11-20 10:00:00')
        ->and([$fresh->fresh()->escalated_at, $done->fresh()->escalated_at])->toBe([null, null])
        ->and($escalated)->toHaveCount(1)
        ->and($escalated->first()->subject_id)->toBe($late->id)
        ->and($escalated->first()->causer_id)->toBeNull();

    // Reopening restarts the count, so the case can pass its target again, and is alerted again.
    $late->update(['status' => Status::Closed]);
    $late->update(['status' => Status::Reopened]);
    expect($late->fresh()->escalated_at)->toBeNull();

    $this->artisan('ghm:recount-working-days');
    expect(alertsOf($this->admin, AlertKind::PastTarget))->toBe(1);

    $this->travel(30)->days();
    $this->artisan('ghm:recount-working-days');
    expect(Notification::sent($this->admin, CaseAlert::class, fn (CaseAlert $alert): bool => $alert->kind === AlertKind::PastTarget && $alert->grievanceId === $late->id))
        ->toHaveCount(2);
})->group('U6', 'A2', 'S6');
