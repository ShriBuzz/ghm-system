<?php

use App\Enums\Role;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Holidays\Pages\ManageHolidays;
use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Holiday;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\PlaceholderListsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function signedInAs(Role $role): User
{
    $user = User::factory()->create([
        'role' => $role,
        'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
    ]);
    test()->actingAs($user);

    return $user;
}

dataset('settings pages', [
    'categories' => ['/admin/categories'],
    'locations' => ['/admin/locations'],
    'holidays' => ['/admin/holidays'],
    'users' => ['/admin/users'],
]);

it('opens every settings page for the Administrator', function (string $url) {
    signedInAs(Role::Administrator);

    $this->get('/admin')->assertOk()->assertSee($url);
    $this->get($url)->assertOk();
})->with('settings pages')->group('A7');

// One role per test: Filament builds the menu once per app, and tests reuse one app across requests.
it('keeps other roles out of every settings page and its menu item', function (Role $role, string $url) {
    signedInAs($role);

    $this->get('/admin')->assertOk()->assertDontSee($url);
    $this->get($url)->assertForbidden();
})->with([Role::FocalPerson, Role::RestrictedOfficer])->with('settings pages')->group('A7');

it('lets the Administrator add and edit a category, restricted flag included', function () {
    signedInAs(Role::Administrator);

    Livewire::test(ManageCategories::class)
        ->callAction('create', data: ['name_en' => 'Wages', 'name_ne' => 'तलब', 'is_restricted' => false, 'is_active' => true, 'sort' => 1])
        ->assertHasNoFormErrors();

    $category = Category::where('name_en', 'Wages')->sole();
    $case = Grievance::factory()->for($category)->create();
    $focal = User::factory()->create(['role' => Role::FocalPerson]);
    expect(Grievance::visibleTo($focal)->pluck('id')->all())->toBe([$case->id]);

    Livewire::test(ManageCategories::class)
        ->callAction(TestAction::make('edit')->table($category), data: ['is_restricted' => true])
        ->assertHasNoFormErrors();

    expect($category->fresh()->is_restricted)->toBeTrue()
        ->and(Grievance::visibleTo($focal)->pluck('id')->all())->toBe([]);
})->group('A7', 'A5');

it('lets the Administrator add locations and holidays, and remove a wrong holiday', function () {
    signedInAs(Role::Administrator);

    Livewire::test(ManageLocations::class)
        ->callAction('create', data: ['name_en' => 'Head office', 'name_ne' => 'केन्द्रीय कार्यालय', 'is_active' => true, 'sort' => 0])
        ->assertHasNoFormErrors();

    Livewire::test(ManageHolidays::class)
        ->callAction('create', data: ['date' => '2026-11-11', 'name' => 'Test holiday'])
        ->assertHasNoFormErrors()
        ->callAction('create', data: ['date' => '2026-11-11', 'name' => 'Same day'])
        ->assertHasFormErrors(['date' => 'unique']);

    Livewire::test(ManageHolidays::class)
        ->callAction(TestAction::make('delete')->table(Holiday::sole()));

    expect(Location::where('name_en', 'Head office')->exists())->toBeTrue()
        ->and(Holiday::count())->toBe(0);
})->group('A7', 'A2');

it('deletes no category, location or user: they are deactivated instead', function () {
    $admin = signedInAs(Role::Administrator);

    expect($admin->can('delete', Category::factory()->create()))->toBeFalse()
        ->and($admin->can('delete', Location::factory()->create()))->toBeFalse()
        ->and($admin->can('delete', User::factory()->create()))->toBeFalse();

    Livewire::test(ManageCategories::class)->assertActionDoesNotExist(TestAction::make('delete')->table(Category::first()));
})->group('A7');

it('lets the Administrator add a user with a hashed password of at least 8 characters', function () {
    signedInAs(Role::Administrator);

    Livewire::test(ManageUsers::class)
        ->callAction('create', data: ['name' => 'New Officer', 'email' => 'officer@example.com', 'role' => Role::RestrictedOfficer->value, 'is_active' => true, 'password' => 'short'])
        ->assertHasFormErrors(['password' => 'min']);

    Livewire::test(ManageUsers::class)
        ->callAction('create', data: ['name' => 'New Officer', 'email' => 'officer@example.com', 'role' => Role::RestrictedOfficer->value, 'is_active' => true, 'password' => 'long-enough-1'])
        ->assertHasNoFormErrors();

    $user = User::where('email', 'officer@example.com')->sole();
    expect($user->role)->toBe(Role::RestrictedOfficer)
        ->and(Hash::check('long-enough-1', $user->password))->toBeTrue();

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($user), data: ['is_active' => false, 'password' => ''])
        ->assertHasNoFormErrors();

    expect($user->fresh()->is_active)->toBeFalse()
        ->and(Hash::check('long-enough-1', $user->fresh()->password))->toBeTrue();
})->group('A7', 'A1');

it('keeps the Administrator from deactivating or demoting themselves', function () {
    $admin = signedInAs(Role::Administrator);

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($admin), data: ['role' => Role::FocalPerson->value, 'is_active' => false]);

    expect($admin->fresh()->role)->toBe(Role::Administrator)
        ->and($admin->fresh()->is_active)->toBeTrue();
})->group('A7', 'A1');

it('seeds the placeholder lists, safely more than once', function () {
    $this->seed(PlaceholderListsSeeder::class);
    $this->seed(PlaceholderListsSeeder::class);

    expect(Category::count())->toBe(5)
        ->and(Category::where('is_restricted', true)->pluck('name_en')->all())->toBe(['Sexual harassment or abuse'])
        ->and(Location::count())->toBe(3);
})->group('A7');
