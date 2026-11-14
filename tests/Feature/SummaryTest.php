<?php

use App\Enums\Role;
use App\Enums\Status;
use App\Filament\Pages\Summary;
use App\Models\Category;
use App\Models\Grievance;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo('2026-11-20 12:00');

    $this->wages = Category::factory()->create(['name_en' => 'Wages']);
    $this->safety = Category::factory()->create(['name_en' => 'Safety']);
    $this->harassment = Category::factory()->create(['name_en' => 'Harassment', 'is_restricted' => true]);

    $case = fn (Category $category, Status $status, string $received) => Grievance::factory()
        ->create(['category_id' => $category->id, 'status' => $status, 'received_at' => $received]);

    $case($this->wages, Status::Received, '2026-11-01 00:05');
    $case($this->wages, Status::Closed, '2026-11-10 09:00');
    $case($this->safety, Status::Received, '2026-11-15 23:55');
    $case($this->harassment, Status::UnderInvestigation, '2026-11-12 10:00');
    $case($this->wages, Status::Received, '2026-10-31 23:59');
    $case($this->safety, Status::Received, '2026-11-16 00:00');
});

function summaryFor(Role $role): array
{
    signedInAs($role);

    return Livewire::test(Summary::class)->fillForm(['from' => '2026-11-01', 'until' => '2026-11-15'])
        ->assertHasNoFormErrors()->instance()->summary();
}

it('counts the cases received in the date range by status and by category', function () {
    expect(summaryFor(Role::Administrator))->toBe([
        'total' => 4,
        'statuses' => [
            'received' => 2, 'under_assessment' => 0, 'under_investigation' => 1, 'referred_out_of_scope' => 0,
            'resolved' => 0, 'closed' => 1, 'reopened' => 0,
        ],
        'categories' => ['Harassment' => 1, 'Safety' => 1, 'Wages' => 2],
    ]);
})->group('N3');

it('counts only the cases each role may see', function () {
    expect(summaryFor(Role::FocalPerson))
        ->total->toBe(3)
        ->statuses->toMatchArray(['received' => 2, 'under_investigation' => 0, 'closed' => 1])
        ->categories->toBe(['Safety' => 1, 'Wages' => 2]);

    expect(summaryFor(Role::RestrictedOfficer))
        ->total->toBe(1)
        ->categories->toBe(['Harassment' => 1]);
})->group('N3', 'A5');

/**
 * The summary's tables as shown, row label => shown count.
 *
 * @return array<string, string>
 */
function shownCounts(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $shown = [];
    foreach ((new DOMXPath($dom))->query('//tr[th[@scope="row"] and td]') as $row) {
        $shown[trim($row->getElementsByTagName('th')->item(0)->textContent)] = trim($row->getElementsByTagName('td')->item(0)->textContent);
    }

    return $shown;
}

it('shows a count from 1 to 4 as "<5", and 0 or 5 and more as they are, with the exact total', function () {
    app()->setLocale('en'); // As SetPanelLocale does for every panel request.
    Grievance::factory()->count(4)->create(['category_id' => $this->wages->id, 'status' => Status::Received, 'received_at' => '2026-11-05 10:00']);
    signedInAs(Role::Administrator);

    $page = Livewire::test(Summary::class)->fillForm(['from' => '2026-11-01', 'until' => '2026-11-15']);

    expect(shownCounts($page->html()))->toMatchArray([
        'Received' => '6', 'Under assessment' => '0', 'Under investigation' => '<5', 'Closed' => '<5',
        'Wages' => '6', 'Safety' => '<5', 'Harassment' => '<5',
    ]);
    $page->assertSee('8 cases received in this period.');
})->group('U11', 'N3');

it('starts at this month and refuses a range that ends before it starts', function () {
    signedInAs(Role::FocalPerson);

    Livewire::test(Summary::class)
        ->assertSchemaStateSet(['from' => '2026-11-01', 'until' => '2026-11-20'])
        ->fillForm(['until' => '2026-10-01'])->assertHasFormErrors(['until'])
        ->assertDontSee('Cases by status');
})->group('N3');

it('is in every role\'s menu', function (Role $role) {
    signedInAs($role);

    $this->get(Summary::getUrl())->assertOk()->assertSee('Cases by status')->assertSee('Cases by category');
})->with([Role::Administrator, Role::FocalPerson, Role::RestrictedOfficer])->group('N3', 'A1');
