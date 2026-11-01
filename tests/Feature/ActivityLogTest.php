<?php

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Grievances\Pages\CreateGrievance;
use App\Filament\Resources\Grievances\Pages\ViewGrievance;
use App\Filament\Resources\Grievances\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Grievances\RelationManagers\NotesRelationManager;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use App\Models\User;
use App\Support\AttachmentStore;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

function logged(string $event): Activity
{
    return Activity::where('event', $event)->latest('id')->firstOrFail();
}

it('logs who entered a case and who viewed it', function () {
    $staff = signedInAs(Role::FocalPerson);

    Livewire::test(CreateGrievance::class)->fillForm([
        'channel' => 'phone', 'received_at' => now()->subHour()->format('Y-m-d H:i'),
        'category_id' => Category::factory()->create()->id, 'location_id' => Location::factory()->create()->id,
        'incident_date' => now()->subDay()->toDateString(), 'recurrence' => 'once', 'description' => 'Test grievance text',
        'is_anonymous' => true,
    ])->call('create')->assertHasNoFormErrors();
    $case = Grievance::sole();

    expect(logged('created'))->subject_id->toBe($case->id)->causer_id->toBe($staff->id);

    $this->get("/admin/grievances/{$case->id}")->assertOk();
    expect(logged('viewed'))->subject_id->toBe($case->id)->causer_id->toBe($staff->id);
})->group('S6');

it('logs status, priority, assignment and restriction changes with old and new values', function () {
    $case = Grievance::factory()->create();
    $colleague = User::factory()->create(['role' => Role::FocalPerson]);
    $staff = signedInAs(Role::FocalPerson);

    Livewire::test(ViewGrievance::class, ['record' => $case->getRouteKey()])
        ->callAction('status', data: ['status' => Status::UnderAssessment->value])
        ->callAction('priority', data: ['priority' => Priority::High->value])
        ->callAction('assign', data: ['assigned_to' => $colleague->id])
        ->callAction('restrict');

    expect(logged('status_changed'))->causer_id->toBe($staff->id)
        ->properties->toArray()->toEqual(['old' => 'received', 'new' => 'under_assessment'])
        ->and(logged('priority_changed')->properties->toArray())->toEqual(['old' => null, 'new' => 'high'])
        ->and(logged('restriction_changed')->properties->toArray())->toEqual(['old' => false, 'new' => true]);

    // Restricting dropped the focal colleague, which is logged as a second assignment change.
    $assignments = Activity::where('event', 'assigned')->orderBy('id')->get()->map->properties->map->toArray()->all();
    expect($assignments)->toEqual([['old' => null, 'new' => $colleague->id], ['old' => $colleague->id, 'new' => null, 'reason' => 'assignee can no longer see the case']]);
})->group('S6');

it('logs notes, replies, uploads and document views', function () {
    Storage::fake('local');
    $case = Grievance::factory()->create();
    signedInAs(Role::FocalPerson);

    $page = ['ownerRecord' => $case, 'pageClass' => ViewGrievance::class];
    Livewire::test(NotesRelationManager::class, $page)->callAction(TestAction::make('create')->table(), data: ['body' => 'Private note text']);
    Livewire::test(MessagesRelationManager::class, $page)->callAction(TestAction::make('create')->table(), data: ['body' => 'Reply text']);
    $attachment = AttachmentStore::store(UploadedFile::fake()->image('photo.jpg'), $case);
    $this->get(route('filament.admin.attachments.download', $attachment))->assertOk();

    foreach (['note_added', 'reply_sent', 'attachment_uploaded', 'attachment_viewed'] as $event) {
        expect(logged($event)->subject_id)->toBe($case->id, $event);
    }
})->group('S6');

it('logs a category becoming restricted', function () {
    $admin = signedInAs(Role::Administrator);
    $category = Category::factory()->create();

    $category->update(['is_restricted' => true]);

    expect(logged('category_restriction_changed'))->subject_id->toBe($category->id)->causer_id->toBe($admin->id)
        ->properties->toArray()->toEqual(['old' => false, 'new' => true]);
})->group('S6', 'A5');

it('logs sign-ins, and failed sign-ins to an account, without the email typed', function () {
    $user = User::factory()->create(['email' => 'staff@example.com']);

    Livewire::test(Login::class)->fillForm(['email' => 'staff@example.com', 'password' => 'wrong-password'])->call('authenticate');
    Livewire::test(Login::class)->fillForm(['email' => 'nobody@example.com', 'password' => 'wrong-password'])->call('authenticate');

    // Only the account's failure is logged: an unknown email has no one to record, and anyone could send endless ones.
    $failures = Activity::where('event', 'login_failed')->get();
    expect($failures)->toHaveCount(1)
        ->and($failures[0]->causer_id)->toBe($user->id)
        ->and($failures->toJson())->not->toContain('example.com');

    $this->actingAs($user);
    event(new Illuminate\Auth\Events\Login('web', $user, false));
    expect(logged('login')->causer_id)->toBe($user->id);
})->group('S6', 'S2');

it('never writes case content into the log', function () {
    Storage::fake('local');
    $case = Grievance::factory()->create(['name' => 'Secret Complainant', 'description' => 'Secret grievance text']);
    signedInAs(Role::Administrator);

    $this->get("/admin/grievances/{$case->id}");
    Livewire::test(ViewGrievance::class, ['record' => $case->getRouteKey()])->callAction('status', data: ['status' => Status::Resolved->value]);
    Livewire::test(NotesRelationManager::class, ['ownerRecord' => $case, 'pageClass' => ViewGrievance::class])
        ->callAction(TestAction::make('create')->table(), data: ['body' => 'Secret note text']);

    expect(Activity::count())->toBeGreaterThan(2)
        ->and(Activity::all()->toJson())->not->toContain('Secret');
})->group('S6', 'S3');

it('shows the log to the Administrator only', function () {
    signedInAs(Role::Administrator);
    $this->get('/admin/activity-log')->assertOk();

    signedInAs(Role::FocalPerson);
    $this->get('/admin/activity-log')->assertForbidden();
})->group('S6');
