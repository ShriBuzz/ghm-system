<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function signIn(string $email, string $password): Testable
{
    return Livewire::test(Login::class)
        ->fillForm(['email' => $email, 'password' => $password])
        ->call('authenticate');
}

function failSignIn(string $email, int $times): void
{
    foreach (range(1, $times) as $attempt) {
        signIn($email, 'wrong-password')->assertHasErrors('data.email');
    }
}

it('locks an email for 15 minutes from the fifth failed sign-in', function () {
    $user = User::factory()->create();

    failSignIn($user->email, 1);
    $this->travel(10)->minutes();
    failSignIn($user->email, 4);

    $this->travel(14)->minutes();
    signIn($user->email, 'password')->assertNotified();
    $this->assertGuest();

    $this->travel(2)->minutes();
    signIn($user->email, 'password')->assertHasNoErrors();
    $this->assertAuthenticatedAs($user);
})->group('S2');

it('locks the email, however it is typed, and no other account', function () {
    $user = User::factory()->create(['email' => 'officer@example.com']);
    $other = User::factory()->create();

    failSignIn('officer@example.com', 5);

    signIn(' Officer@Example.com ', 'password')->assertNotified();
    $this->assertGuest();

    signIn($other->email, 'password')->assertHasNoErrors();
    $this->assertAuthenticatedAs($other);
})->group('S2');

it('clears failed attempts after a successful sign-in', function () {
    $user = User::factory()->create();

    failSignIn($user->email, 4);
    signIn($user->email, 'password');
    Filament::auth()->logout();

    $this->travel(2)->minutes();
    failSignIn($user->email, 4);
    signIn($user->email, 'password')->assertHasNoErrors();
    $this->assertAuthenticatedAs($user);
})->group('S2');

it('keeps IP addresses out of the sign-in throttles', function () {
    signIn('someone@example.com', 'wrong-password');

    expect(RateLimiter::attempts('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1')))->toBe(0)
        ->and(RateLimiter::attempts('login-throttle:'.hash('sha256', 'someone@example.com')))->toBe(1);
})->group('S2', 'S5');

it('serves the hardened sign-in page at /admin/login', function () {
    $this->get('/admin/login')->assertSeeLivewire(Login::class);
})->group('S2');

it('offers no "remember me", so sessions always expire', function () {
    Livewire::test(Login::class)->assertFormFieldDoesNotExist('remember');
})->group('S2');

it('never polls the server for notifications', function () {
    $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);

    expect(Filament::getPanel('admin')->hasDatabaseNotifications())->toBeFalse();

    $this->actingAs($user)->get('/admin')->assertOk()->assertDontSee('wire:poll');
})->group('S2');

it('loads the idle script with the session lifetime on every panel page', function () {
    $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $script = '<script src="'.asset('js/admin-idle.js').'" data-lifetime-minutes="'.config('session.lifetime').'" defer></script>';

    $this->get('/admin/login')->assertSee($script, escape: false);
    $this->actingAs($user)->get('/admin')->assertSee($script, escape: false);
})->group('S2');
