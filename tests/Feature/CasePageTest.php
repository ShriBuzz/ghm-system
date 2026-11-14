<?php

use App\Enums\MessageDirection;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Filament\Resources\Grievances\Pages\ListGrievances;
use App\Filament\Resources\Grievances\Pages\ViewGrievance;
use App\Filament\Resources\Grievances\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Grievances\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Grievances\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Holidays\Pages\ManageHolidays;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\GrievanceNote;
use App\Models\Holiday;
use App\Models\Location;
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

it('sets status and priority from the fixed lists only', function () {
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

it('lets only the Administrator remove a restriction, with a reason kept as an internal note', function () {
    $case = Grievance::factory()->create(['manually_restricted' => true]);

    signedInAs(Role::RestrictedOfficer);
    viewCase($case)->assertActionHidden('unrestrict')->assertActionHidden('restrict');

    $admin = signedInAs(Role::Administrator);
    viewCase($case)->callAction('unrestrict', data: ['reason' => ''])->assertHasActionErrors(['reason' => 'required']);
    expect($case->fresh()->is_restricted)->toBeTrue()
        ->and($case->notes()->count())->toBe(0);

    viewCase($case)->callAction('unrestrict', data: ['reason' => 'Reviewed: about wages, not harassment.'])->assertHasNoActionErrors();

    $note = $case->notes()->sole();
    expect($case->fresh()->is_restricted)->toBeFalse()
        ->and($note->body)->toBe('Reviewed: about wages, not harassment.')
        ->and($note->user_id)->toBe($admin->id);
})->group('A5', 'U5');

it('keeps a new assignment when the old assignee was loaded and can no longer see the case', function () {
    $old = User::factory()->create(['role' => Role::FocalPerson]);
    $new = User::factory()->create(['role' => Role::FocalPerson]);
    $case = Grievance::factory()->create(['assigned_to' => $old->id]);
    $old->forceFill(['role' => Role::RestrictedOfficer])->save();

    $case->load('assignee'); // as the case page does, to show the name
    $case->update(['assigned_to' => $new->id]);

    expect($case->fresh()->assigned_to)->toBe($new->id);
})->group('A3', 'A5');

it('drops a user from cases they can no longer see when their role changes', function () {
    $user = User::factory()->create(['role' => Role::FocalPerson]);
    $open = Grievance::factory()->create(['assigned_to' => $user->id]);
    $other = Grievance::factory()->create(['assigned_to' => $user->id]);

    $user->update(['role' => Role::Administrator]);
    expect($open->fresh()->assigned_to)->toBe($user->id);

    $user->update(['role' => Role::RestrictedOfficer]);
    expect($open->fresh()->assigned_to)->toBeNull()
        ->and($other->fresh()->assigned_to)->toBeNull();
})->group('A3', 'A5');

it('drops unseeing assignees past the first thousand cases', function () {
    $category = Category::factory()->create();
    $focal = User::factory()->create(['role' => Role::FocalPerson]);
    Grievance::withoutEvents(fn () => Grievance::factory()->count(1001)->for($category)->for(Location::factory()->create())
        ->create(['assigned_to' => $focal->id]));

    $category->forceFill(['is_restricted' => true])->save();
    expect(Grievance::whereNotNull('assigned_to')->count())->toBe(0);

    $officer = User::factory()->create(['role' => Role::RestrictedOfficer]);
    Grievance::query()->update(['assigned_to' => $officer->id]);

    $officer->update(['role' => Role::FocalPerson]);
    expect(Grievance::whereNotNull('assigned_to')->count())->toBe(0);
})->group('A3', 'A5');

it('clears every assignment of a user who is deactivated', function () {
    $user = User::factory()->create(['role' => Role::FocalPerson]);
    $case = Grievance::factory()->create(['assigned_to' => $user->id]);
    $untouched = Grievance::factory()->create(['assigned_to' => User::factory()->create()->id]);

    $user->update(['is_active' => false]);

    expect($case->fresh()->assigned_to)->toBeNull()
        ->and($untouched->fresh()->assigned_to)->not->toBeNull();
})->group('A3', 'A7');

it('shows status and category with their Nepali names, as the complainant sees them', function () {
    $case = Grievance::factory()->create([
        'status' => Status::UnderAssessment,
        'category_id' => Category::factory()->create(['name_en' => 'Wages and benefits', 'name_ne' => 'तलब तथा सुविधा']),
    ]);
    app()->setLocale('en'); // As SetPanelLocale does for every panel request.
    signedInAs(Role::FocalPerson);

    viewCase($case)->assertSee('Under assessment (प्रारम्भिक मूल्याङ्कनको क्रममा)')->assertSee('Wages and benefits (तलब तथा सुविधा)');
})->group('A3', 'A4', 'U16');

it('shows the complainant\'s Nepali text and its line breaks as written, in the submission and the messages', function () {
    $case = Grievance::factory()->create(['description' => "पहिलो लाइन: तीन महिनादेखि तलब आएको छैन।\nदोस्रो लाइन: <b>सुपरभाइजर</b>लाई भनें।"]);
    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Complainant, 'body' => "थप जानकारी।\nआज फेरि भयो।"]);
    signedInAs(Role::FocalPerson);

    // Each line break becomes <br />; anything typed as HTML stays visible text.
    viewCase($case)->assertSeeHtml('पहिलो लाइन: तीन महिनादेखि तलब आएको छैन।<br />')->assertSee('दोस्रो लाइन:')
        ->assertSeeHtml('&lt;b&gt;सुपरभाइजर&lt;/b&gt;')->assertDontSeeHtml('<b>सुपरभाइजर</b>');
    Livewire::test(MessagesRelationManager::class, ['ownerRecord' => $case, 'pageClass' => ViewGrievance::class])
        ->assertSeeHtml('थप जानकारी।<br />')->assertSee('आज फेरि भयो।');
})->group('A3', 'U16');

it('shows every date staff read in Bikram Sambat with AD alongside', function () {
    $this->travelTo('2026-11-08 10:00');
    $case = Grievance::factory()->create(['incident_date' => '2026-10-28', 'incident_date_exact' => true]);
    GrievanceNote::factory()->for($case)->create(['body' => 'A note']);
    Holiday::forceCreate(['date' => '2026-11-08', 'name' => 'A holiday']);
    Holiday::forceCreate(['date' => '2207-05-13', 'name' => 'Beyond the BS table']);
    // Each later date has its own time, so each assertion can only match its own field.
    $this->travelTo('2026-11-08 11:30');
    $case->forceFill(['status' => Status::Closed])->save();
    $this->travelTo('2026-11-08 12:15');
    GrievanceMessage::factory()->for($case)->create();
    $this->travelTo('2026-11-08 13:45');
    Attachment::factory()->for($case)->create();
    signedInAs(Role::Administrator);

    viewCase($case)->assertSee('2083-07-22 (2026-11-08) 10:00')->assertSee('2083-07-11 (2026-10-28)')
        ->assertSee('2083-07-22 (2026-11-08) 11:30');
    $page = ['ownerRecord' => $case, 'pageClass' => ViewGrievance::class];
    Livewire::test(MessagesRelationManager::class, $page)->assertSee('2083-07-22 (2026-11-08) 12:15');
    Livewire::test(AttachmentsRelationManager::class, $page)->assertSee('2083-07-22 (2026-11-08) 13:45');
    Livewire::test(ListGrievances::class)->assertSee('2083-07-22 (2026-11-08)');
    Livewire::test(NotesRelationManager::class, ['ownerRecord' => $case, 'pageClass' => ViewGrievance::class])->assertSee('2083-07-22 (2026-11-08) 10:00');
    Livewire::test(ManageActivityLogs::class)->assertSee('2083-07-22 (2026-11-08) 10:00');
    Livewire::test(ManageHolidays::class)->assertSee('2083-07-22 (2026-11-08)')->assertSee('2207-05-13');
})->group('A2', 'A3', 'U9');
