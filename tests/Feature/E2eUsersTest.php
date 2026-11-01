<?php

use App\Enums\Role;
use App\Models\Category;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * @return array<string, array{email: string, password: string, secret: string}>
 */
function e2eUsers(): array
{
    Artisan::call('ghm:e2e-users');

    return json_decode(Artisan::output(), true);
}

it('gives Playwright one user per role, signed in with the printed password and code', function () {
    $first = e2eUsers();
    $second = e2eUsers();

    expect(array_keys($second))->toBe(array_column(Role::cases(), 'value'))
        ->and(User::count())->toBe(3)
        ->and(Category::count())->toBeGreaterThan(0)
        ->and($second['focal_person']['password'])->not->toBe($first['focal_person']['password'])
        ->and($second['focal_person']['secret'])->not->toBe($first['focal_person']['secret']);

    foreach ($second as $role => $credentials) {
        $user = User::where('email', $credentials['email'])->sole();

        expect($user->role)->toBe(Role::from($role))
            ->and(Hash::check($credentials['password'], $user->password))->toBeTrue()
            ->and($user->app_authentication_secret)->toBe($credentials['secret']);

        $this->actingAs($user);
        expect(AppAuthentication::make()->verifyCode(AppAuthentication::make()->getCurrentCode($user)))->toBeTrue();
    }
})->group('A1');

it('refuses to create them on a server', function (string $environment) {
    app()->detectEnvironment(fn () => $environment);

    $this->artisan('ghm:e2e-users')->assertFailed();

    expect(User::count())->toBe(0);
})->with(['production', 'staging'])->group('A1');
