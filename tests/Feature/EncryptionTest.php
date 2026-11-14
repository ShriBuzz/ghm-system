<?php

use App\Models\Attachment;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\GrievanceNote;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('encrypts the session on disk, where the PIN waits until Done', function () {
    $sessions = storage_path('framework/testing/sessions-'.Str::random(8));
    File::ensureDirectoryExists($sessions);
    config(['session.driver' => 'file', 'session.files' => $sessions]);

    $this->post('/en', formInput())->assertRedirect(route('grievance.received', ['locale' => 'en']));
    $pin = session('received.pin');
    $stored = collect(File::files($sessions))->map->getContents()->implode('');
    File::deleteDirectory($sessions);

    expect($pin)->toMatch('/^\d{6}$/')->and($stored)->not->toBe('')->not->toContain($pin);
})->group('S3', 'P7');

it('stores the field as ciphertext', function (string $model, string $column) {
    $record = $model::factory()->create([$column => 'Sensitive test value']);

    $stored = DB::table($record->getTable())->where('id', $record->id)->value($column);

    expect($stored)->not->toContain('Sensitive test value')
        ->and(Crypt::decryptString($stored))->toBe('Sensitive test value');
})->with([
    'grievance name' => [Grievance::class, 'name'],
    'grievance phone' => [Grievance::class, 'phone'],
    'grievance email' => [Grievance::class, 'email'],
    'grievance description' => [Grievance::class, 'description'],
    'grievance complainant type' => [Grievance::class, 'complainant_type'],
    'grievance sex' => [Grievance::class, 'sex'],
    'grievance age group' => [Grievance::class, 'age_group'],
    'grievance ethnicity' => [Grievance::class, 'ethnicity'],
    'grievance disability' => [Grievance::class, 'disability'],
    'message body' => [GrievanceMessage::class, 'body'],
    'note body' => [GrievanceNote::class, 'body'],
    'attachment original name' => [Attachment::class, 'original_name'],
])->group('S3');
