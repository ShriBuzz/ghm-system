<?php

use App\Enums\Channel;
use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Resources\Grievances\Pages\CreateGrievance;
use App\Filament\Resources\Grievances\Pages\ListGrievances;
use App\Filament\Resources\Grievances\Pages\RecordedCase;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * A complete offline entry, as staff would type it.
 *
 * @return array<string, mixed>
 */
function offlineCase(array $overrides = []): array
{
    return array_merge([
        'channel' => Channel::DropBox->value,
        'received_at' => '2026-11-01 09:00',
        'category_id' => Category::factory()->create()->id,
        'location_id' => Location::factory()->create()->id,
        'incident_date' => '2026-10-28',
        'recurrence' => 'repeated',
        'description' => 'Overtime is not paid for night shifts.',
        'is_anonymous' => false,
        'name' => 'Test Worker',
        'phone' => '9800000000',
        'email' => null,
        'preferred_contact' => 'phone',
    ], $overrides);
}

it('records an offline case with its channel, reference and a hashed PIN', function () {
    $this->travelTo('2026-11-02 10:00');
    $staff = signedInAs(Role::FocalPerson);

    Livewire::test(CreateGrievance::class)
        ->fillForm(offlineCase())
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(RecordedCase::getUrl());

    $case = Grievance::sole();
    $slip = session('recorded_case');

    expect($case->channel)->toBe(Channel::DropBox)
        ->and($case->received_at->toDateTimeString())->toBe('2026-11-01 09:00:00')
        ->and($case->created_by)->toBe($staff->id)
        ->and($case->status)->toBe(Status::Received)
        ->and($case->reference)->toBe('GHM-2026-0001')
        ->and($case->name)->toBe('Test Worker')
        ->and($slip['reference'])->toBe('GHM-2026-0001')
        ->and($slip['pin'])->toMatch('/^\d{6}$/')
        ->and(Hash::check($slip['pin'], $case->pin_hash))->toBeTrue()
        ->and(DB::table('grievances')->value('pin_hash'))->not->toBe($slip['pin']);
})->group('A6', 'P7');

it('offers no "create another", which would skip the slip and lose the PIN', function () {
    signedInAs(Role::FocalPerson);

    Livewire::test(CreateGrievance::class)->assertActionDoesNotExist('createAnother');
})->group('A6');

it('shows the reference and PIN once on the slip page', function () {
    signedInAs(Role::FocalPerson);

    session()->flash('recorded_case', ['reference' => 'GHM-2026-0007', 'pin' => '048213', 'received_at' => '2026-11-01 09:00']);
    session()->ageFlashData(); // as at the end of the request that saved the case

    $this->get(RecordedCase::getUrl())
        ->assertOk()
        ->assertSee('GHM-2026-0007')
        ->assertSee('048213');

    $this->get(RecordedCase::getUrl())->assertRedirect(ListGrievances::getUrl());
})->group('A6');

it('prints the slip without the panel menu, and leaves other pages as they print', function () {
    signedInAs(Role::FocalPerson);

    session()->flash('recorded_case', ['reference' => 'GHM-2026-0007', 'pin' => '048213', 'received_at' => '2026-11-01 09:00']);
    session()->ageFlashData();

    $this->get(RecordedCase::getUrl())->assertSee('@media print', escape: false);
    $this->get(ListGrievances::getUrl())->assertDontSee('@media print', escape: false);
})->group('A6');

it('keeps the PIN flashed for the slip out of the session file in clear', function () {
    $dir = storage_path('framework/testing/sessions-'.Str::random(8));
    File::ensureDirectoryExists($dir);
    config(['session.files' => $dir]);

    $session = app('session')->driver('file');
    $session->start();
    $session->flash('recorded_case', ['reference' => 'GHM-2026-0007', 'pin' => '048213']);
    $session->save();

    $stored = File::get("{$dir}/{$session->getId()}");
    File::deleteDirectory($dir);

    expect($stored)->not->toContain('048213');
})->group('A6', 'P7');

it('lets a focal person enter a case into a restricted category and still get the slip', function () {
    signedInAs(Role::FocalPerson);

    Livewire::test(CreateGrievance::class)
        ->fillForm(offlineCase(['category_id' => Category::factory()->restricted()->create()->id]))
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(RecordedCase::getUrl());

    expect(session('recorded_case.reference'))->toBe(Grievance::sole()->reference)
        ->and(Grievance::sole()->is_restricted)->toBeTrue();
})->group('A6', 'A5');

it('stores no contact details for an anonymous case, even if some were typed', function () {
    signedInAs(Role::FocalPerson);

    Livewire::test(CreateGrievance::class)
        ->fillForm(offlineCase(['name' => 'Typed First', 'is_anonymous' => true]))
        ->call('create')
        ->assertHasNoFormErrors();

    $case = Grievance::sole();
    expect($case->is_anonymous)->toBeTrue()
        ->and($case->name)->toBeNull()
        ->and($case->phone)->toBeNull()
        ->and($case->preferred_contact)->toBeNull();
})->group('A6', 'P4');

it('refuses an incomplete or invalid entry', function (array $overrides, string $field) {
    signedInAs(Role::FocalPerson);

    Livewire::test(CreateGrievance::class)
        ->fillForm(offlineCase($overrides))
        ->call('create')
        ->assertHasFormErrors([$field]);

    expect(Grievance::count())->toBe(0);
})->with([
    'web is not an offline channel' => [['channel' => Channel::Web->value], 'channel'],
    'named without phone or email' => [['phone' => null, 'email' => null], 'phone'],
    'named without a name' => [['name' => null], 'name'],
    'prefers email but gave only a phone' => [['preferred_contact' => 'email'], 'email'],
    'prefers phone but gave only an email' => [['preferred_contact' => 'phone', 'phone' => null, 'email' => 'worker@example.com'], 'phone'],
    'incident in the future' => [['incident_date' => now()->addDay()->toDateString()], 'incident_date'],
    'inactive category' => [fn () => ['category_id' => Category::factory()->create(['is_active' => false])->id], 'category_id'],
    'no description' => [['description' => ''], 'description'],
])->group('A6', 'P4', 'P5');
