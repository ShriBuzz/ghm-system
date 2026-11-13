<?php

use App\Models\Grievance;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as Events;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Events\UnhealthyBackupWasFound;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;

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

it('dumps the database without tablespaces, which a shared-hosting MySQL user may not read', function () {
    // MySQL 8.0.21+ refuses a plain mysqldump to a user without the PROCESS privilege, as on most cPanel hosts.
    $command = DbDumperFactory::createFromConnection('mysql')->getDumpCommand('dump.sql', 'credentials.cnf');

    expect($command)->toContain('--no-tablespaces');
})->group('S7');

it('holds case details in the backup only as ciphertext, which the app key alone decrypts', function () {
    Storage::fake('backups');
    config(['backup.backup.password' => 'test-archive-password']);
    app()->forgetInstance(Config::class);

    // mysqldump reads through its own connection and cannot see the test's open transaction,
    // so this case is committed, then deleted again whatever happens.
    $case = Grievance::factory()->create(['name' => 'Backup Test Complainant', 'description' => 'Backup test: wages unpaid']);
    DB::commit();

    try {
        $stored = DB::table('grievances')->where('id', $case->id)->value('description');
        $this->artisan('backup:run --only-db --disable-notifications')->assertSuccessful();
    } finally {
        DB::table('activity_log')->where('subject_type', $case->getMorphClass())->where('subject_id', $case->id)->delete();
        DB::table('grievances')->where('id', $case->id)->delete();
        DB::table('categories')->where('id', $case->category_id)->delete();
        DB::table('locations')->where('id', $case->location_id)->delete();
    }

    $zip = new ZipArchive;
    $zip->open(Storage::disk('backups')->path(Storage::disk('backups')->allFiles()[0]));
    $zip->setPassword('test-archive-password');
    $dump = (string) $zip->getFromName('db-dumps/mysql-'.config('database.connections.mysql.database').'.sql');
    $otherKey = new Encrypter(Encrypter::generateKey(config('app.cipher')), config('app.cipher'));

    expect($dump)->toContain('INSERT INTO `grievances`')
        ->toContain($stored)
        ->not->toContain('Backup test: wages unpaid')
        ->not->toContain('Backup Test Complainant')
        ->and(Crypt::decryptString($stored))->toBe('Backup test: wages unpaid')
        ->and(fn () => $otherKey->decryptString($stored))->toThrow(DecryptException::class);
})->group('X2', 'S3', 'S7');

it('says in php artisan about, the first check after a deploy, whether backups are encrypted', function () {
    config(['backup.backup.password' => null]);
    $this->artisan('about', ['--only' => 'backups'])->expectsOutputToContain('NOT SET')->assertSuccessful();

    config(['backup.backup.password' => 'an-archive-password']);
    $this->artisan('about', ['--only' => 'backups'])->expectsOutputToContain('Archive password')
        ->doesntExpectOutputToContain('archives are not encrypted')->assertSuccessful();
})->group('S7');

it('says in php artisan about whether anyone is told when a backup fails', function () {
    // BACKUP_MAIL_TO left out of .env: failures and missing backups would go unseen.
    config(['backup.notifications.notifications.'.BackupHasFailedNotification::class => []]);
    $this->artisan('about', ['--only' => 'backups'])->expectsOutputToContain('nobody is told')->assertSuccessful();

    config(['backup.notifications.notifications.'.BackupHasFailedNotification::class => ['mail']]);
    $this->artisan('about', ['--only' => 'backups'])->expectsOutputToContain('Failure emails')
        ->doesntExpectOutputToContain('nobody is told')->assertSuccessful();
})->group('S7');

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

it('never deletes a backup younger than 15 days for size, and warns when the backups grow too big instead', function () {
    Storage::fake('backups');
    Events::fake([UnhealthyBackupWasFound::class]);
    $this->travelTo('2026-11-20 01:30');
    $folder = config('backup.backup.name');

    // 14 nights of 400 MB archives, 5,600 MB in all: sparse files, so they take no real disk space.
    foreach (range(0, 13) as $daysAgo) {
        $date = now()->subDays($daysAgo)->setTime(1, 0);
        $path = Storage::disk('backups')->path("{$folder}/{$date->format('Y-m-d-H-i-s')}.zip");
        File::ensureDirectoryExists(dirname($path));
        $file = fopen($path, 'w');
        ftruncate($file, 400 * 1024 * 1024);
        fclose($file);
        touch($path, $date->getTimestamp());
    }

    $this->artisan('backup:clean --disable-notifications')->assertSuccessful();
    expect(Storage::disk('backups')->files($folder))->toHaveCount(14);

    // Too big now: the monitor reports it, which emails BACKUP_MAIL_TO.
    $this->artisan('backup:monitor')->assertFailed();
    Events::assertDispatched(UnhealthyBackupWasFound::class);

    File::deleteDirectory(Storage::disk('backups')->path($folder));
})->group('S7');
