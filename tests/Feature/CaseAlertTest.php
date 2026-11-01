<?php

use App\Enums\MessageDirection;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\User;
use App\Notifications\CaseAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

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

it('sends a queued English email with no case details, only a sign-in link', function () {
    $case = Grievance::factory()->create(['description' => 'Wages unpaid for three months']);
    $alert = new CaseAlert($case->id, message: false);

    $mail = $alert->toMail($this->focal);
    $text = implode(' ', [$mail->subject, ...$mail->introLines, ...$mail->outroLines]);

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
})->group('N1');
