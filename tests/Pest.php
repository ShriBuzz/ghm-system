<?php

use App\Enums\Role;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
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

/**
 * What the public form sends for a valid anonymous grievance, as strings, like a browser,
 * including a fill-time stamp from ten seconds ago (P9).
 *
 * @return array<string, mixed>
 */
function formInput(array $overrides = []): array
{
    return [
        'is_anonymous' => '1',
        'category_id' => (string) Category::factory()->create()->id,
        'location_id' => (string) Location::factory()->create()->id,
        'incident_day' => '28',
        'incident_month' => '10',
        'incident_year' => '2026',
        'recurrence' => 'once',
        'description' => 'Wages unpaid for three months',
        'form_started' => Crypt::encryptString((string) now()->subSeconds(10)->getTimestamp()),
        'website' => '',
        ...$overrides,
    ];
}
