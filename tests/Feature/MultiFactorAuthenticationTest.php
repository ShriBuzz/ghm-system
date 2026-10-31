<?php

use App\Enums\Role;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('sends every role to authenticator setup until MFA is on', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get('/admin')
        ->assertRedirect(Filament::getPanel('admin')->getSetUpRequiredMultiFactorAuthenticationUrl());
})->with(Role::cases())->group('S10');

it('lets a user with MFA on reach the panel', function () {
    $user = User::factory()->create(['app_authentication_secret' => AppAuthentication::make()->generateSecret()]);

    $this->actingAs($user)->get('/admin')->assertOk();
})->group('S10');

it('signs in only after a valid authenticator code', function () {
    $mfa = AppAuthentication::make();
    $secret = $mfa->generateSecret();
    $user = User::factory()->create(['app_authentication_secret' => $secret]);

    $login = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertNotSet('userUndertakingMultiFactorAuthentication', null);

    $this->assertGuest();

    $login->set('data.multiFactor.app.code', $mfa->getCurrentCode($user, $secret) === '000000' ? '111111' : '000000')
        ->call('authenticate')
        ->assertHasErrors();

    $this->assertGuest();

    $login->set('data.multiFactor.app.code', $mfa->getCurrentCode($user, $secret))
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user);
})->group('S10');

it('stores the authenticator secret encrypted', function () {
    $secret = AppAuthentication::make()->generateSecret();
    $user = User::factory()->create(['app_authentication_secret' => $secret]);

    $stored = DB::table('users')->where('id', $user->id)->value('app_authentication_secret');

    expect($stored)->not->toContain($secret)
        ->and(Crypt::decryptString($stored))->toBe($secret);
})->group('S10');
