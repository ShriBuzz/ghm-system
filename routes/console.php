<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Grievance;
use App\Models\User;
use App\Support\WorkingDays;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

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

// Shared hosting has no supervisor: cron's schedule:run starts a worker that exits once the queue is empty.
Schedule::command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping();

// A refused email's error can quote the recipient; keep failed jobs a week for retrying, no longer (S5).
Schedule::command('queue:prune-failed --hours=168')->daily();

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

    $this->line((string) json_encode($credentials));
})->purpose('A fake user with a fresh password and MFA secret for the Playwright specs (A1)');
