<?php

use App\Enums\Role;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // Livewire keeps static per-request state that a real request never carries over.
    ->beforeEach(fn () => Livewire::flushState())
    ->in('Feature');

/**
 * Sign in as a new staff member with the given role, past the required MFA setup.
 */
function signedInAs(Role $role): User
{
    $user = User::factory()->create([
        'role' => $role,
        'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
    ]);
    test()->actingAs($user);

    return $user;
}
