<?php

use App\Models\Grievance;
use App\Models\User;
use App\Support\AttachmentStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => Storage::fake('local'));

/**
 * A JPEG carrying an EXIF block (orientation 6, "rotate 90°") and a comment
 * with a marker, the way a phone photo carries GPS and device data.
 */
function phonePhoto(int $width = 40, int $height = 20): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($image);
    $jpeg = (string) ob_get_clean();

    $tiff = 'II'.pack('v', 42).pack('V', 8).pack('v', 1).pack('vvVvv', 0x0112, 3, 1, 6, 0).pack('V', 0);
    $exif = "Exif\0\0".$tiff;
    $comment = 'GPS 27.7172N 85.3240E Phone X';
    $segments = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif
        ."\xFF\xFE".pack('n', strlen($comment) + 2).$comment;

    return fakeUpload('photo.jpg', substr($jpeg, 0, 2).$segments.substr($jpeg, 2));
}

function fakeUpload(string $name, string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'ghm');
    file_put_contents($path, $contents);

    return new UploadedFile($path, $name, null, null, true);
}

it('stores a photo without its metadata, turned the way it was taken', function () {
    $grievance = Grievance::factory()->create();
    $staff = User::factory()->create();

    $attachment = AttachmentStore::store(phonePhoto(), $grievance, $staff);

    $stored = Storage::disk('local')->get(AttachmentStore::path($attachment));
    [$width, $height] = getimagesizefromstring($stored);

    expect($stored)->not->toContain('GPS')
        ->and($stored)->not->toContain('Exif')
        ->and([$width, $height])->toBe([20, 40])
        ->and($attachment->grievance_id)->toBe($grievance->id)
        ->and($attachment->uploaded_by)->toBe($staff->id)
        ->and($attachment->original_name)->toBe('photo.jpg')
        ->and($attachment->mime)->toBe('image/jpeg')
        ->and($attachment->size)->toBe(strlen($stored))
        ->and($attachment->stored_name)->not->toContain('photo');
})->group('S4', 'S5');

it('accepts PNG, WebP and PDF', function (string $name, string $mime) {
    $file = match ($mime) {
        'application/pdf' => fakeUpload($name, "%PDF-1.4\n%%EOF\n"),
        default => UploadedFile::fake()->image($name, 10, 10),
    };

    expect(AttachmentStore::store($file, Grievance::factory()->create())->mime)->toBe($mime);
})->with([
    ['scan.png', 'image/png'],
    ['photo.webp', 'image/webp'],
    ['letter.pdf', 'application/pdf'],
])->group('P5', 'S4');

it('rejects a file that is too big or not what its name says', function (UploadedFile $file) {
    expect(fn () => AttachmentStore::store($file, Grievance::factory()->create()))
        ->toThrow(ValidationException::class);

    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'over 5 MB' => fn () => fakeUpload('big.pdf', "%PDF-1.4\n".str_repeat('0', 5 * 1024 * 1024 + 1024)),
    'program renamed to .jpg' => fn () => fakeUpload('photo.jpg', 'MZ'.str_repeat("\0", 200)),
    'text file' => fn () => fakeUpload('note.pdf', 'just some text'),
])->group('P5', 'S4');

it('keeps files on a private disk with no route of its own', function () {
    expect(config('filesystems.disks.local.root'))->not->toStartWith(public_path())
        ->and(Route::has('storage.local'))->toBeFalse();
})->group('S4');
