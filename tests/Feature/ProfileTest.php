<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => AppAuthentication::make()->generateSecret()]);
    $this->actingAs($this->user);
});

it('lets staff change their own password when they give the current one', function () {
    $this->get('/admin/profile')->assertOk();

    Livewire::test(EditProfile::class)
        ->fillForm(['password' => 'a-new-long-password', 'passwordConfirmation' => 'a-new-long-password', 'currentPassword' => 'password'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('a-new-long-password', $this->user->fresh()->password))->toBeTrue();
})->group('S2');

it('refuses a new password without the right current one', function () {
    Livewire::test(EditProfile::class)
        ->fillForm(['password' => 'a-new-long-password', 'passwordConfirmation' => 'a-new-long-password', 'currentPassword' => 'wrong-password'])
        ->call('save')
        ->assertHasFormErrors(['currentPassword']);

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
})->group('S2');

it('keeps IP addresses out of the profile throttle', function () {
    Livewire::test(EditProfile::class)->call('save');

    expect(RateLimiter::attempts('livewire-rate-limiter:'.sha1(EditProfile::class.'|save|127.0.0.1')))->toBe(0)
        ->and(RateLimiter::attempts('profile-throttle:'.$this->user->id))->toBe(1);
})->group('S2', 'S5');
