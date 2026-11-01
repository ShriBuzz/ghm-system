<?php

use App\Enums\Role;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * @return array{email: string, password: string, secret: ?string}
 */
function e2eUser(string $role, string $key, bool $withoutMfa = false): array
{
    Artisan::call('ghm:e2e-user', ['role' => $role, 'key' => $key, '--without-mfa' => $withoutMfa]);

    return json_decode(Artisan::output(), true);
}

it('gives Playwright a user who signs in with the printed password and code', function () {
    $first = e2eUser('focal_person', 'focal_person-0');
    $second = e2eUser('focal_person', 'focal_person-0');
    $user = User::where('email', 'e2e-focal_person-0@example.com')->sole();

    expect($user->role)->toBe(Role::FocalPerson)
        ->and($user->name)->toBe('E2E Focal person 0')
        ->and(Hash::check($second['password'], $user->password))->toBeTrue()
        ->and($user->app_authentication_secret)->toBe($second['secret'])
        ->and($second['password'])->not->toBe($first['password'])
        ->and($second['secret'])->not->toBe($first['secret']);

    $this->actingAs($user);
    expect(AppAuthentication::make()->verifyCode(AppAuthentication::make()->getCurrentCode($user)))->toBeTrue();
})->group('A1');

it('can leave MFA off, for the first-time setup spec', function () {
    e2eUser('administrator', 'enrol-0');
    $credentials = e2eUser('administrator', 'enrol-0', withoutMfa: true);

    expect($credentials['secret'])->toBeNull()
        ->and(User::where('email', $credentials['email'])->sole()->app_authentication_secret)->toBeNull();
})->group('A1', 'S10');

it('creates them on staging, which holds fake data only', function () {
    app()->detectEnvironment(fn () => 'staging');

    $this->artisan('ghm:e2e-user', ['role' => 'administrator', 'key' => 'administrator-0'])->assertSuccessful();
})->group('A1');

it('refuses production, an unknown role and an odd key', function (string $environment, string $role, string $key) {
    app()->detectEnvironment(fn () => $environment);

    $this->artisan('ghm:e2e-user', ['role' => $role, 'key' => $key])->assertFailed();

    expect(User::count())->toBe(0);
})->with([
    'production' => ['production', 'administrator', 'administrator-0'],
    'unknown role' => ['local', 'superuser', 'superuser-0'],
    'odd key' => ['local', 'administrator', 'x@evil.example'],
])->group('A1');
