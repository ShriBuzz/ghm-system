<?php

use App\Enums\MessageDirection;
use App\Enums\Role;
use App\Filament\Resources\Grievances\Pages\ViewGrievance;
use App\Filament\Resources\Grievances\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Grievances\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Grievances\RelationManagers\NotesRelationManager;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\GrievanceNote;
use App\Support\AttachmentStore;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function onCasePage(string $relationManager, Grievance $case): Testable
{
    return Livewire::test($relationManager, ['ownerRecord' => $case, 'pageClass' => ViewGrievance::class]);
}

it('adds dated internal notes with their author, which cannot be edited or deleted', function () {
    $case = Grievance::factory()->create();
    $staff = signedInAs(Role::FocalPerson);

    onCasePage(NotesRelationManager::class, $case)
        ->callAction(TestAction::make('create')->table(), data: ['body' => 'Called the supervisor; meeting on Monday.'])
        ->assertHasNoActionErrors();

    $note = GrievanceNote::sole();
    expect($note->grievance_id)->toBe($case->id)
        ->and($note->user_id)->toBe($staff->id)
        ->and($note->body)->toBe('Called the supervisor; meeting on Monday.')
        ->and($note->created_at)->not->toBeNull();

    onCasePage(NotesRelationManager::class, $case)
        ->assertCanSeeTableRecords([$note])
        ->assertActionDoesNotExist(TestAction::make('edit')->table($note))
        ->assertActionDoesNotExist(TestAction::make('delete')->table($note));
})->group('A3');

it('sends a staff reply into the complainant thread, next to their messages', function () {
    $case = Grievance::factory()->create();
    $fromComplainant = GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Complainant]);
    $staff = signedInAs(Role::FocalPerson);

    onCasePage(MessagesRelationManager::class, $case)
        ->assertCanSeeTableRecords([$fromComplainant])
        ->callAction(TestAction::make('create')->table(), data: ['body' => 'We have spoken to the payroll office.'])
        ->assertHasNoActionErrors();

    $reply = GrievanceMessage::where('direction', MessageDirection::Staff)->sole();
    expect($reply->author_id)->toBe($staff->id)
        ->and($reply->body)->toBe('We have spoken to the payroll office.');

    onCasePage(MessagesRelationManager::class, $case)
        ->assertActionDoesNotExist(TestAction::make('edit')->table($reply))
        ->assertActionDoesNotExist(TestAction::make('delete')->table($reply));
})->group('A3');

it('uploads a staff document to the private store', function () {
    Storage::fake('local');
    $case = Grievance::factory()->create();
    $staff = signedInAs(Role::FocalPerson);

    onCasePage(AttachmentsRelationManager::class, $case)
        ->callAction(TestAction::make('upload')->table(), data: ['file' => UploadedFile::fake()->image('site-visit.jpg')])
        ->assertHasNoActionErrors();

    $attachment = Attachment::sole();
    expect($attachment->uploaded_by)->toBe($staff->id)
        ->and($attachment->original_name)->toBe('site-visit.jpg')
        ->and(Storage::disk('local')->exists(AttachmentStore::path($attachment)))->toBeTrue();
})->group('A3', 'S4');

it('deletes Livewire\'s temporary copy, which still holds the photo\'s metadata', function () {
    Storage::fake('local');
    Storage::fake('tmp-for-tests');
    signedInAs(Role::FocalPerson);

    // Livewire turns the upload into a temporary file on this disk, as a real upload does.
    onCasePage(AttachmentsRelationManager::class, Grievance::factory()->create())
        ->callAction(TestAction::make('upload')->table(), data: ['file' => UploadedFile::fake()->image('photo.jpg')])
        ->assertHasNoActionErrors();

    expect(Attachment::count())->toBe(1)
        ->and(Storage::disk('tmp-for-tests')->allFiles())->toBe([]);
})->group('A3', 'S5');

it('refuses a document that is not a photo or PDF', function () {
    Storage::fake('local');
    signedInAs(Role::FocalPerson);

    onCasePage(AttachmentsRelationManager::class, Grievance::factory()->create())
        ->callAction(TestAction::make('upload')->table(), data: ['file' => UploadedFile::fake()->create('notes.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])
        ->assertHasActionErrors(['file']);

    expect(Attachment::count())->toBe(0);
})->group('A3', 'S4');

it('downloads a document only for staff who can see its case', function () {
    Storage::fake('local');
    $restricted = Grievance::factory()->for(Category::factory()->restricted())->create();
    $attachment = AttachmentStore::store(UploadedFile::fake()->image('evidence.png'), $restricted);
    $url = route('filament.admin.attachments.download', $attachment);

    $this->get($url)->assertRedirect('/admin/login');

    signedInAs(Role::FocalPerson);
    $this->get($url)->assertForbidden();

    signedInAs(Role::RestrictedOfficer);
    $this->get($url)
        ->assertOk()
        ->assertDownload('evidence.png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
})->group('S4', 'A5');
