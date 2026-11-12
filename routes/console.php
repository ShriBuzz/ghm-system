<?php

use App\Enums\AlertKind;
use App\Enums\Role;
use App\Enums\Status;
use App\Models\Grievance;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

Artisan::command('ghm:recount-working-days', function () {
    // Quietly: a system job, not a staff action, so no observer or staff activity entry.
    Grievance::whereIn('status', Status::open())->lazyById()->each(function (Grievance $grievance): void {
        $grievance->working_days_open = $grievance->workingDaysSoFar();

        // Past its target for the first time since it was received or reopened: tell the Administrators once (U6).
        // shortcut: every case's target is the RFQ's 15 working days until per-category targets (U1) are agreed.
        $escalate = $grievance->escalated_at === null && $grievance->working_days_open > Config::integer('ghm.sla_days');
        if ($escalate) {
            $grievance->escalated_at = now();
        }

        $grievance->saveQuietly();

        if ($escalate) {
            activity()->performedOn($grievance)->event('escalated')
                ->withProperties(['working_days' => $grievance->working_days_open])->log('Past target');
            $grievance->alertStaff(AlertKind::PastTarget);
        }
    });
})->purpose('Recount working days open for open cases (A2), and tell the Administrators of a case past its target (U6)');

Schedule::command('ghm:recount-working-days')->dailyAt('00:30');

Artisan::command('ghm:clear-temporary-uploads', function () {
    // Livewire clears these only when a later upload finishes, so a file chosen in a dialog that was then
    // cancelled, an uncleaned original with its name, could wait weeks. Same age rule as Livewire's (S5).
    $storage = FileUploadConfiguration::storage();

    foreach ($storage->allFiles(FileUploadConfiguration::path()) as $file) {
        if ($storage->lastModified($file) < now()->subDay()->getTimestamp()) {
            $storage->delete($file);
        }
    }
})->purpose('Delete temporary uploads older than a day (S5)');

Schedule::command('ghm:clear-temporary-uploads')->hourly();

Artisan::command('ghm:prune-cache', function () {
    // The file cache deletes an expired entry only when its key is read again. Sign-in counters for
    // emails never tried twice would stay on disk for good, and a bot trying many emails could fill
    // the host's file quota (S2). Each file starts with its expiry time; 0 or unreadable is skipped.
    // Walked lazily, not listed and sorted first, so a folder a bot filled cannot exhaust memory.
    foreach (Finder::create()->files()->in(Config::string('cache.stores.file.path')) as $file) {
        $expires = (int) file_get_contents($file->getPathname(), length: 10);

        if ($expires > 0 && $expires <= now()->getTimestamp()) {
            File::delete($file->getPathname());
        }
    }
})->purpose('Delete expired file-cache entries (S2)');

// Hourly: a bot trying a new email each time leaves two files per attempt.
Schedule::command('ghm:prune-cache')->hourlyAt(15);

// Shared hosting has no supervisor: cron's schedule:run starts a worker that exits once the queue is empty.
// The lock expires after 10 minutes, so a worker killed by the host does not stop email for a day.
// A failed email is tried again after 1 minute, 10 minutes and an hour, so a mail-server outage or a
// host's hourly sending limit delays an alert instead of losing it; a later cron run picks it up.
Schedule::command('queue:work --stop-when-empty --tries=4 --backoff=60,600,3600')->everyMinute()->withoutOverlapping(10);

// Failed jobs are kept a week for `php artisan queue:retry`, no longer. Their error never holds a
// refused email's address (App\Support\FailedJobStore), and an acknowledgement's job is encrypted.
Schedule::command('queue:prune-failed --hours=168')->daily();

// S7: the night's backup, then the 14-day cleanup, then a check that a fresh backup exists.
Schedule::command('backup:run')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:monitor')->dailyAt('02:00');

Artisan::command('ghm:e2e-user {role} {key} {--without-mfa}', function (string $role, string $key) {
    // For the Playwright specs (tests/e2e/support.ts): a fake user with a fresh password and
    // authenticator secret on every call, printed as JSON, so no credential is kept in the repo.
    // Staging holds fake data only and runs the full suite before UAT; production never does.
    if (! app()->environment('local', 'testing', 'staging')) {
        $this->error('Never on production.');

        return 1;
    }

    if (! Role::tryFrom($role) || ! preg_match('/^[a-z0-9_-]{1,40}$/', $key)) {
        $this->error('Usage: ghm:e2e-user <role> <key>, the key in lower-case letters, digits, - and _.');

        return 1;
    }

    $credentials = [
        'email' => "e2e-{$key}@example.com",
        'password' => Str::password(32),
        'secret' => $this->option('without-mfa') ? null : AppAuthentication::make()->generateSecret(),
    ];

    User::firstOrNew(['email' => $credentials['email']])->forceFill([
        'name' => 'E2E '.Str::of($key)->replace(['_', '-'], ' ')->ucfirst(),
        'password' => $credentials['password'],
        'role' => Role::from($role),
        'is_active' => true,
        'app_authentication_secret' => $credentials['secret'],
        'app_authentication_recovery_codes' => null,
    ])->save();

    // Raw: the console formatter would read a "\<" in the password as an escaped "<" and break the JSON.
    $this->output->writeln((string) json_encode($credentials), OutputInterface::OUTPUT_RAW);
})->purpose('A fake user with a fresh password and MFA secret for the Playwright specs (A1)');
