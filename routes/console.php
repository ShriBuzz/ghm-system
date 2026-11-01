<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Grievance;
use App\Models\User;
use App\Support\WorkingDays;
use Database\Seeders\PlaceholderListsSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

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

Artisan::command('ghm:e2e-users', function () {
    // For Playwright's sign-in (tests/e2e/auth.setup.ts): fresh credentials on every
    // run, so none are kept in the repo, and a fresh secret, so no code is reused.
    if (! app()->environment('local', 'testing')) {
        $this->error('Only on a developer machine or in CI.');

        return 1;
    }

    // Last run's sign-ins must not count against Filament's five-per-minute limits.
    $this->callSilently('cache:clear');
    app(PlaceholderListsSeeder::class)->run();

    $users = collect(Role::cases())->mapWithKeys(function (Role $role): array {
        $credentials = [
            'email' => "e2e-{$role->value}@example.com",
            'password' => Str::password(32),
            'secret' => AppAuthentication::make()->generateSecret(),
        ];

        User::firstOrNew(['email' => $credentials['email']])->forceFill([
            'name' => 'E2E '.Str::headline($role->value),
            'password' => $credentials['password'],
            'role' => $role,
            'is_active' => true,
            'app_authentication_secret' => $credentials['secret'],
        ])->save();

        return [$role->value => $credentials];
    });

    $this->line($users->toJson());
})->purpose('Fake staff, one per role, with fresh passwords and MFA secrets for the Playwright specs (A1)');
