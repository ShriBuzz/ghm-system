<?php

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Resources\Grievances\Pages\ListGrievances;
use App\Filament\Resources\Grievances\Pages\ViewGrievance;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function viewCase(Grievance $case): Testable
{
    return Livewire::test(ViewGrievance::class, ['record' => $case->getRouteKey()]);
}

it('shows the submission, with no contact details for an anonymous case', function () {
    $named = Grievance::factory()->create(['name' => 'Test Complainant', 'description' => 'The canteen water is unsafe.']);
    $anonymous = Grievance::factory()->create(['is_anonymous' => true, 'name' => null, 'phone' => null, 'email' => null]);

    signedInAs(Role::FocalPerson);

    viewCase($named)->assertSee('Test Complainant')->assertSee('The canteen water is unsafe.')->assertSee($named->category->name_en);
    viewCase($anonymous)->assertSee('Anonymous')->assertDontSee('Preferred contact');
})->group('A3');

it('sets status and priority from the A4 lists only', function () {
    $case = Grievance::factory()->create();
    signedInAs(Role::FocalPerson);

    viewCase($case)
        ->callAction('status', data: ['status' => Status::UnderAssessment->value])->assertHasNoActionErrors()
        ->callAction('priority', data: ['priority' => Priority::High->value])->assertHasNoActionErrors();

    expect($case->fresh()->status)->toBe(Status::UnderAssessment)
        ->and($case->fresh()->priority)->toBe(Priority::High);

    viewCase($case)->callAction('status', data: ['status' => 'archived'])->assertHasActionErrors(['status']);
})->group('A3', 'A4');

it('offers assignment only to active users who can see the case', function () {
    $admin = User::factory()->create(['role' => Role::Administrator]);
    $focal = User::factory()->create(['role' => Role::FocalPerson]);
    $officer = User::factory()->create(['role' => Role::RestrictedOfficer]);
    User::factory()->create(['role' => Role::FocalPerson, 'is_active' => false]);

    $open = Grievance::factory()->create();
    $restricted = Grievance::factory()->for(Category::factory()->restricted())->create();

    expect($open->assignableUsers()->pluck('id')->sort()->values()->all())->toBe([$admin->id, $focal->id])
        ->and($restricted->assignableUsers()->pluck('id')->sort()->values()->all())->toBe([$admin->id, $officer->id]);

    signedInAs(Role::RestrictedOfficer);
    viewCase($restricted)->callAction('assign', data: ['assigned_to' => $focal->id])->assertHasActionErrors(['assigned_to']);
    viewCase($restricted)->callAction('assign', data: ['assigned_to' => $officer->id])->assertHasNoActionErrors();

    expect($restricted->fresh()->assigned_to)->toBe($officer->id);
})->group('A3', 'A5');

it('drops an assignee who can no longer see the case once it is restricted', function () {
    $focal = User::factory()->create(['role' => Role::FocalPerson]);
    $marked = Grievance::factory()->create(['assigned_to' => $focal->id]);
    $category = Category::factory()->create();
    $inCategory = Grievance::factory()->for($category)->create(['assigned_to' => $focal->id]);

    $marked->forceFill(['manually_restricted' => true])->save();
    $category->forceFill(['is_restricted' => true])->save();

    expect($marked->fresh()->assigned_to)->toBeNull()
        ->and($inCategory->fresh()->assigned_to)->toBeNull();
})->group('A3', 'A5');

it('lets a focal person mark a case restricted, then sends them back to the list', function () {
    $case = Grievance::factory()->create();
    signedInAs(Role::FocalPerson);

    viewCase($case)->callAction('restrict')->assertRedirect(ListGrievances::getUrl());

    expect($case->fresh()->is_restricted)->toBeTrue();
})->group('A5');

it('lets only the Administrator remove a restriction', function () {
    $case = Grievance::factory()->create(['manually_restricted' => true]);

    signedInAs(Role::RestrictedOfficer);
    viewCase($case)->assertActionHidden('unrestrict')->assertActionHidden('restrict');

    signedInAs(Role::Administrator);
    viewCase($case)->callAction('unrestrict');

    expect($case->fresh()->is_restricted)->toBeFalse();
})->group('A5');
