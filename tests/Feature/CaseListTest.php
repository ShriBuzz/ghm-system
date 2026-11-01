<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Resources\Grievances\GrievanceResource;
use App\Filament\Resources\Grievances\Pages\ListGrievances;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use Filament\Tables\Columns\TextColumn;
use Livewire\Livewire;

/**
 * A case open for the given number of working days.
 */
function caseOpenFor(int $days, array $attributes = []): Grievance
{
    $case = Grievance::factory()->create($attributes);
    Grievance::whereKey($case->id)->update(['working_days_open' => $days]);

    return $case->fresh();
}

it('lists only the cases the signed-in role may see', function () {
    $open = Grievance::factory()->create();
    $restricted = Grievance::factory()->for(Category::factory()->restricted())->create();

    signedInAs(Role::FocalPerson);
    Livewire::test(ListGrievances::class)->assertCanSeeTableRecords([$open])->assertCanNotSeeTableRecords([$restricted]);

    signedInAs(Role::RestrictedOfficer);
    Livewire::test(ListGrievances::class)->assertCanSeeTableRecords([$restricted])->assertCanNotSeeTableRecords([$open]);

    signedInAs(Role::Administrator);
    Livewire::test(ListGrievances::class)->assertCanSeeTableRecords([$open, $restricted]);
})->group('A2', 'A5');

it('keeps a focal person out of a restricted case opened by its address', function () {
    $restricted = Grievance::factory()->for(Category::factory()->restricted())->create();

    signedInAs(Role::FocalPerson);
    expect($this->get("/admin/grievances/{$restricted->id}")->status())->toBeIn([403, 404]);

    signedInAs(Role::RestrictedOfficer);
    $this->get("/admin/grievances/{$restricted->id}")->assertOk()->assertSee($restricted->reference);
})->group('A5');

it('searches by reference number', function () {
    $wanted = Grievance::factory()->create(['reference' => 'GHM-2026-0042']);
    $other = Grievance::factory()->create(['reference' => 'GHM-2026-0007']);

    signedInAs(Role::Administrator);
    Livewire::test(ListGrievances::class)
        ->searchTable('0042')
        ->assertCanSeeTableRecords([$wanted])
        ->assertCanNotSeeTableRecords([$other]);
})->group('A2');

it('filters by status, category, location and received date', function () {
    $category = Category::factory()->create();
    $location = Location::factory()->create();
    $match = Grievance::factory()->for($category)->for($location)->create(['received_at' => '2026-11-10 10:00']);
    $other = Grievance::factory()->create(['received_at' => '2026-10-01 10:00']);
    $other->forceFill(['status' => Status::Resolved])->save();

    signedInAs(Role::Administrator);
    $list = fn () => Livewire::test(ListGrievances::class);

    $list()->filterTable('status', [Status::Received->value])->assertCanSeeTableRecords([$match])->assertCanNotSeeTableRecords([$other]);
    $list()->filterTable('category', $category->id)->assertCanSeeTableRecords([$match])->assertCanNotSeeTableRecords([$other]);
    $list()->filterTable('location', $location->id)->assertCanSeeTableRecords([$match])->assertCanNotSeeTableRecords([$other]);
    $list()->filterTable('received', ['from' => '2026-11-01', 'until' => '2026-11-30'])->assertCanSeeTableRecords([$match])->assertCanNotSeeTableRecords([$other]);
})->group('A2');

it('marks open cases past 15 working days and filters them', function () {
    $day15 = caseOpenFor(15);
    $day16 = caseOpenFor(16);
    $resolved = caseOpenFor(20);
    Grievance::whereKey($resolved->id)->update(['status' => Status::Resolved->value]);
    $resolved = $resolved->fresh();

    signedInAs(Role::Administrator);
    $list = Livewire::test(ListGrievances::class);

    foreach ([[$day15, 'gray'], [$day16, 'danger'], [$resolved, 'gray']] as [$case, $colour]) {
        $list->assertTableColumnExists('working_days_open', fn (TextColumn $column) => $column->getColor($column->getState()) === $colour, $case);
    }

    $list->filterTable('overdue')
        ->assertCanSeeTableRecords([$day16])
        ->assertCanNotSeeTableRecords([$day15, $resolved]);
})->group('A2');

it('offers offline entry from the list, and no way to edit or delete a case', function () {
    signedInAs(Role::Administrator);

    Livewire::test(ListGrievances::class)->assertActionExists('create');
    expect(array_keys(GrievanceResource::getPages()))->toBe(['index', 'create', 'recorded', 'view']);
})->group('A2', 'A6');
