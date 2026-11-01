<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Backup\Config\Config;

function scheduled(string $command): ?Event
{
    return collect(app(Schedule::class)->events())->first(fn (Event $event) => str_contains((string) $event->command, $command));
}

it('backs up every night at 01:00, then cleans up and checks the backups', function () {
    expect(scheduled('backup:run')?->expression)->toBe('0 1 * * *')
        ->and(scheduled('backup:clean')?->expression)->toBe('30 1 * * *')
        ->and(scheduled('backup:monitor')?->expression)->toBe('0 2 * * *');
})->group('S7');

it('writes a password-protected archive of the database and private files, without upload leftovers or secrets', function () {
    Storage::fake('backups');
    config(['backup.backup.password' => 'test-archive-password']);
    app()->forgetInstance(Config::class); // The package reads its config once; read it again with the password.

    $mark = Str::random(8);
    $kept = storage_path("app/private/attachments/backup-test-{$mark}.pdf");
    $leftover = storage_path("app/private/livewire-tmp/backup-test-{$mark}.pdf");
    File::ensureDirectoryExists(dirname($kept));
    File::ensureDirectoryExists(dirname($leftover));
    File::put($kept, 'fake attachment');
    File::put($leftover, 'fake cancelled upload');

    try {
        $this->artisan('backup:run --disable-notifications')->assertSuccessful();
    } finally {
        File::delete([$kept, $leftover]);
    }

    $archive = Storage::disk('backups')->allFiles();
    expect($archive)->toHaveCount(1);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('backups')->path($archive[0]));
    $entries = collect(range(0, $zip->numFiles - 1))->map(fn (int $i) => $zip->statIndex($i));
    $names = $entries->pluck('name')->implode("\n");

    expect($names)->toContain('db-dumps/mysql-')
        ->toContain("\nattachments/backup-test-{$mark}.pdf")
        ->not->toContain('livewire-tmp')
        ->not->toContain('.env')
        // Every file is encrypted with AES-256; folder entries hold nothing.
        ->and($entries->reject(fn (array $entry) => str_ends_with($entry['name'], '/'))
            ->every(fn (array $entry) => $entry['encryption_method'] === ZipArchive::EM_AES_256))->toBeTrue();
})->group('S7', 'S4');

it('keeps backups for 14 days', function () {
    Storage::fake('backups');
    $this->travelTo('2026-11-20 01:30');
    $folder = config('backup.backup.name');

    foreach ([20, 15, 14, 13, 1] as $daysAgo) {
        $date = now()->subDays($daysAgo)->setTime(1, 0);
        Storage::disk('backups')->put("{$folder}/{$date->format('Y-m-d-H-i-s')}.zip", 'fake');
        touch(Storage::disk('backups')->path("{$folder}/{$date->format('Y-m-d-H-i-s')}.zip"), $date->getTimestamp());
    }

    $this->artisan('backup:clean --disable-notifications')->assertSuccessful();

    expect(Storage::disk('backups')->files($folder))->toBe([
        "{$folder}/2026-11-06-01-00-00.zip",
        "{$folder}/2026-11-07-01-00-00.zip",
        "{$folder}/2026-11-19-01-00-00.zip",
    ]);
})->group('S7');
