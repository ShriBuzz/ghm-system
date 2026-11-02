<?php

use App\Models\Grievance;
use App\Models\User;
use App\Notifications\Acknowledgement;
use App\Notifications\CaseAlert;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

function mailWorker(): Event
{
    $worker = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, 'queue:work'));
    assert($worker instanceof Event);

    return $worker;
}

/**
 * Queue emails in the jobs table and have the mail server refuse each one as it refuses an
 * unknown address: quoting it.
 */
function refuseEmails(): void
{
    Mail::extend('refusing', fn () => new class extends AbstractTransport
    {
        protected function doSend(SentMessage $message): void
        {
            throw new TransportException('Expected response code "250" but got code "550", with message "550 5.1.1 <complainant@example.com>: Recipient address rejected".', 550);
        }

        public function __toString(): string
        {
            return 'refusing';
        }
    });

    config(['mail.mailers.refusing' => ['transport' => 'refusing'], 'mail.default' => 'refusing', 'queue.default' => 'database']);
}

it('tries a refused email again after a minute, ten minutes and an hour, before giving up on it', function () {
    refuseEmails();
    $this->freezeTime();
    Grievance::factory()->create(['email' => 'complainant@example.com'])->acknowledge('048213');
    // The command cron runs; --sleep=0 only skips the worker's 3-second pause before it exits.
    $cron = 'queue:work'.Str::after(mailWorker()->command, 'queue:work').' --sleep=0';

    foreach ([60, 600, 3600] as $wait) {
        $this->artisan($cron)->assertSuccessful();

        expect(DB::table('jobs')->sole()->available_at)->toBe(now()->addSeconds($wait)->getTimestamp())
            ->and(DB::table('failed_jobs')->count())->toBe(0);

        $this->travel($wait)->seconds();
    }

    $this->artisan($cron)->assertSuccessful();

    expect(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(1);
})->group('N1', 'N2');

it('loads nothing from another site in an email, whatever the app is called', function () {
    // Laravel's own mail header shows a logo from laravel.com while the app is named "Laravel",
    // which tells laravel.com the reader's IP address when the email is opened.
    config(['app.name' => 'Laravel']);
    $case = Grievance::factory()->create(['email' => 'complainant@example.com']);

    $emails = (string) (new CaseAlert($case->id, message: false))->toMail(User::factory()->create())->render()
        .(string) (new Acknowledgement('048213'))->toMail($case)->render();

    expect($emails)->not->toContain('laravel.com')->not->toContain('<img');
})->group('N1', 'N2', 'S5');

it('works the mail queue from cron every minute, one worker at a time', function () {
    $worker = mailWorker();

    expect($worker->command)->toContain('--stop-when-empty')
        ->and($worker->expression)->toBe('* * * * *')
        ->and($worker->withoutOverlapping)->toBeTrue()
        ->and($worker->expiresAt)->toBe(10);
})->group('N1', 'N2');

it('logs a refused email without the address the mail server quoted', function () {
    Log::spy();

    report(new TransportException('Expected response code "250" but got code "550", with message "550 5.1.1 <complainant@example.com>: Recipient address rejected".', 550));

    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message) => str_contains($message, '550')
        && ! str_contains($message, 'complainant@example.com'));
})->group('N1', 'N2', 'S5');

it('records a failed email without the address the mail server quoted', function () {
    refuseEmails();
    Grievance::factory()->create(['email' => 'complainant@example.com'])->acknowledge('048213');

    $this->artisan('queue:work --stop-when-empty --tries=1 --sleep=0')->assertSuccessful();

    expect(DB::table('failed_jobs')->sole()->exception)->toContain('mail server code 550')
        ->not->toContain('complainant@example.com');
})->group('N2', 'S3', 'S5');

it('deletes failed jobs after 7 days', function () {
    $prune = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, 'queue:prune-failed'));

    expect($prune)->not->toBeNull()
        ->and($prune->command)->toContain('--hours=168')
        ->and($prune->expression)->toBe('0 0 * * *');
})->group('N1', 'N2', 'S5');
