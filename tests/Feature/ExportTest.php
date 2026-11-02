<?php

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Filament\Resources\Grievances\Pages\ListGrievances;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use App\Models\User;
use App\Support\CaseExport;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Spatie\Activitylog\Models\Activity;

const N4_COLUMNS = ['Reference', 'Received', 'Closed', 'Channel', 'Category', 'Location', 'Priority', 'Status',
    'Complainant type', 'Sex', 'Age group', 'Ethnicity', 'Disability'];

beforeEach(function () {
    app()->setLocale('en'); // The staff panel's language (clarification question 14).
    $this->travelTo('2026-11-20 12:00');

    $this->case = Grievance::factory()->create([
        'reference' => 'GHM-2026-0001',
        'received_at' => '2026-11-03 09:15',
        'channel' => 'phone',
        'category_id' => Category::factory()->create(['name_en' => 'Wages'])->id,
        'location_id' => Location::factory()->create(['name_en' => 'Head office'])->id,
        'priority' => Priority::High,
        'status' => Status::Received,
        'name' => '=HYPERLINK("http://example.com","Click")',
        'phone' => '+9779800000000',
        'email' => 'complainant@example.com',
        'complainant_type' => 'worker', 'sex' => 'female', 'age_group' => '25_34', 'ethnicity' => 'prefer_not', 'disability' => 'no',
    ]);
    $this->case->forceFill(['status' => Status::Closed])->save();
    Grievance::factory()->create(['received_at' => '2026-10-31 23:59']);
    Grievance::factory()->create(['received_at' => '2026-11-05 10:00', 'category_id' => Category::factory()->restricted()]);
});

/**
 * Runs an export and returns its rows, header first.
 *
 * @return list<list<string>>
 */
function exportRows(Role|User $user, string $format = 'csv', bool $personal = false): array
{
    $user = $user instanceof User ? $user : User::factory()->create(['role' => $user]);
    $response = CaseExport::download($user, Carbon::parse('2026-11-01'), Carbon::parse('2026-11-15'), $format, $personal);

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    if ($format === 'csv') {
        expect($content)->toStartWith("\u{FEFF}"); // So Excel reads Nepali text as UTF-8.

        return array_map(str_getcsv(...), explode("\n", trim(substr($content, 3))));
    }

    $file = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($file, $content);
    $reader = new Reader;
    $reader->open($file);
    $rows = [];
    foreach ($reader->getSheetIterator()->current()->getRowIterator() as $row) {
        $rows[] = array_map(strval(...), $row->toArray());
    }
    $reader->close();
    unlink($file);

    return $rows;
}

it('exports the N4 columns for the cases received in the range, without names or contacts', function (string $format) {
    $rows = exportRows(Role::Administrator, $format);

    expect($rows[0])->toBe(N4_COLUMNS)
        ->and($rows)->toHaveCount(3)
        ->and($rows[1])->toBe(['GHM-2026-0001', '2026-11-03 09:15', '2026-11-20 12:00', 'Phone', 'Wages', 'Head office', 'High', 'Closed',
            'worker', 'female', '25_34', 'prefer_not', 'no'])
        ->and(implode(' ', array_merge(...$rows)))->not->toContain('complainant@example.com')->not->toContain('9800000000');
})->with(['csv', 'xlsx'])->group('N4');

it('adds names and contacts only when the Administrator asks, with formulas made harmless', function () {
    $rows = exportRows(Role::Administrator, personal: true);

    expect($rows[0])->toBe([...array_slice(N4_COLUMNS, 0, 1), 'Name', 'Phone', 'Email', ...array_slice(N4_COLUMNS, 1)])
        ->and(array_slice($rows[1], 0, 4))->toBe(['GHM-2026-0001', "'=HYPERLINK(\"http://example.com\",\"Click\")", "'+9779800000000", 'complainant@example.com']);
})->group('N4');

it('never gives names or contacts to other roles, and only the cases they may see', function () {
    foreach ([Role::FocalPerson, Role::RestrictedOfficer] as $role) {
        $rows = exportRows($role, personal: true);

        expect($rows[0])->toBe(N4_COLUMNS)->and($rows)->toHaveCount(2);
    }
})->group('N4', 'A5');

it('logs each export with its range, format and whether personal data was included', function () {
    $admin = User::factory()->create(['role' => Role::Administrator]);
    exportRows($admin, 'xlsx', personal: true);

    $entry = Activity::where('event', 'exported')->sole();
    expect($entry->causer_id)->toBe($admin->id)
        ->and($entry->properties->all())->toEqual(['from' => '2026-11-01', 'until' => '2026-11-15', 'format' => 'xlsx', 'personal_data' => true, 'cases' => 2]);
})->group('N4', 'S6');

it('offers the personal-data choice to the Administrator only', function () {
    signedInAs(Role::FocalPerson);
    Livewire::test(ListGrievances::class)
        ->mountAction('export')->assertActionDataSet(['format' => 'csv'])
        ->assertFormFieldHidden('personal');

    signedInAs(Role::Administrator);
    Livewire::test(ListGrievances::class)
        ->callAction(TestAction::make('export'), data: ['from' => '2026-11-01', 'until' => '2026-11-15', 'format' => 'csv', 'personal' => true])
        ->assertHasNoFormErrors()
        ->assertFileDownloaded('cases-2026-11-01-to-2026-11-15.csv');
})->group('N4');

it('shows an export on the activity log page with its details', function () {
    $admin = signedInAs(Role::Administrator);
    exportRows($admin, personal: true);

    Livewire::test(ManageActivityLogs::class)
        ->assertSee('Exported')
        ->assertSee(['from: 2026-11-01', 'until: 2026-11-15', 'format: csv', 'personal_data: yes', 'cases: 2']);
})->group('N4', 'S6');
