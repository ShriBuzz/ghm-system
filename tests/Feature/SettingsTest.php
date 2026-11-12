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
use Database\Seeders\CompanyListsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

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

it('refuses a category name already in use, even by an inactive one, so the summary keeps them apart', function () {
    signedInAs(Role::Administrator);
    Category::factory()->create(['name_en' => 'Wages', 'name_ne' => 'तलब', 'is_active' => false]);

    Livewire::test(ManageCategories::class)
        ->callAction('create', data: ['name_en' => 'wages', 'name_ne' => 'तलब', 'is_restricted' => false, 'is_active' => true, 'sort' => 1])
        ->assertHasFormErrors(['name_en' => 'unique', 'name_ne' => 'unique']);

    expect(Category::where('name_en', 'Wages')->count())->toBe(1);
})->group('A7', 'N3');

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

it('loads the company\'s categories and locations into empty lists, in production too', function () {
    app()['env'] = 'production';
    config(['ghm.admin' => ['name' => 'Test Administrator', 'email' => 'admin@example.com', 'password' => 'a-long-test-password']]);

    // As the deploy guide runs it on a new server.
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(Category::count())->toBe(19)
        ->and(Category::orderBy('sort')->first()->only('name_en', 'name_ne'))->toBe(['name_en' => 'Wages and benefits', 'name_ne' => 'तलब तथा सुविधा'])
        ->and(Category::where('is_restricted', true)->orderBy('sort')->pluck('name_en')->all())->toBe([
            'Sexual harassment, exploitation and abuse', 'Reporting wrongdoing (fraud, corruption, breach of law)',
        ])
        ->and(Location::orderBy('sort')->pluck('name_en')->all())->toBe(['Head office, Thapathali', 'Factory, Hetauda', 'Other']);
})->group('A7', 'U5', 'U8');

it('leaves lists the Administrator has already edited alone when seeded again', function () {
    $this->seed(CompanyListsSeeder::class);
    Category::where('name_en', 'Other')->update(['name_en' => 'Something else']);
    Location::where('name_en', 'Other')->update(['name_en' => 'Elsewhere']);

    $this->seed(CompanyListsSeeder::class);

    expect(Category::count())->toBe(19)->and(Category::where('name_en', 'Other')->exists())->toBeFalse()
        ->and(Location::count())->toBe(3)->and(Location::where('name_en', 'Other')->exists())->toBeFalse();
})->group('A7', 'U8');
