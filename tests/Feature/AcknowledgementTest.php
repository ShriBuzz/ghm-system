<?php

use App\Enums\Role;
use App\Filament\Resources\Grievances\Pages\CreateGrievance;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use App\Notifications\Acknowledgement;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('emails the reference and PIN of an offline case to the email given, in the case language', function () {
    Notification::fake();
    signedInAs(Role::FocalPerson);

    Livewire::test(CreateGrievance::class)->fillForm([
        'channel' => 'phone',
        'received_at' => now()->toDateTimeString(),
        'is_anonymous' => false,
        'name' => 'Test Complainant',
        'email' => 'complainant@example.com',
        'preferred_contact' => 'email',
        'category_id' => Category::factory()->create()->id,
        'location_id' => Location::factory()->create()->id,
        'incident_date' => now()->subDay()->toDateString(),
        'recurrence' => 'once',
        'description' => 'Wages unpaid for three months',
    ])->call('create')->assertHasNoFormErrors();

    $case = Grievance::sole();
    $pin = session('recorded_case')['pin'];

    Notification::assertSentTo($case, Acknowledgement::class, function (Acknowledgement $notification, array $channels, Grievance $notifiable) use ($pin) {
        return $channels === ['mail'] && $notification->locale === $notifiable->locale
            && $notifiable->routeNotificationFor('mail') === 'complainant@example.com'
            && str_contains((string) $notification->toMail($notifiable)->render(), $pin);
    });
})->group('N2', 'P7');

it('sends nothing when the complainant gave no email', function () {
    Notification::fake();

    Grievance::factory()->create(['email' => null])->acknowledge('123456');
    Grievance::factory()->create(['is_anonymous' => true, 'name' => null, 'phone' => null, 'email' => null])->acknowledge('123456');

    Notification::assertSentTimes(Acknowledgement::class, 0);
})->group('N2');

it('gives the reference, PIN and where to check them, never what the complainant wrote', function (string $locale, string $subject) {
    $case = Grievance::factory()->create(['email' => 'complainant@example.com', 'locale' => $locale, 'description' => 'Wages unpaid for three months']);
    app()->setLocale($locale);

    $mail = (new Acknowledgement('048213'))->toMail($case);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe($subject)
        ->and($html)->toContain($case->reference)
        ->toContain('048213')
        ->toContain(route('home', ['locale' => $locale]))
        ->not->toContain((string) $case->name)
        ->not->toContain('Wages unpaid')
        ->not->toContain('laravel.com');
})->with([
    ['en', 'We have received your grievance'],
    ['ne', 'तपाईंको गुनासो प्राप्त भयो'],
])->group('N2', 'P7');

it('queues the email encrypted, so the PIN and address are never in the jobs table in clear', function () {
    config(['queue.default' => 'database']);

    $case = Grievance::factory()->create(['email' => 'complainant@example.com']);
    $case->acknowledge('048213');

    expect(new Acknowledgement('048213'))->toBeInstanceOf(ShouldQueue::class)->toBeInstanceOf(ShouldBeEncrypted::class);

    $payloads = DB::table('jobs')->pluck('payload')->implode(' ');
    expect($payloads)->toContain('Acknowledgement')
        ->not->toContain('048213')
        ->not->toContain('complainant@example.com');
})->group('N2', 'P7', 'S3');
