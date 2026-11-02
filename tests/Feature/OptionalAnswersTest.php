<?php

use App\Enums\Role;
use App\Filament\Resources\Grievances\Pages\ViewGrievance;
use App\Http\Requests\StoreGrievanceRequest;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

/**
 * A complete, valid case as the public form sends it, before the optional answers.
 *
 * @return array<string, mixed>
 */
function caseInput(array $overrides = []): array
{
    return [
        'is_anonymous' => true,
        'category_id' => Category::factory()->create()->id,
        'location_id' => Location::factory()->create()->id,
        'incident_date' => now()->subDay()->toDateString(),
        'recurrence' => 'once',
        'description' => 'Wages unpaid for three months',
        ...$overrides,
    ];
}

it('offers "prefer not to say" for every optional question, in both languages', function () {
    expect(Grievance::OPTIONAL_ANSWERS)->toHaveKeys(['complainant_type', 'sex', 'age_group', 'ethnicity', 'disability']);

    foreach (Grievance::OPTIONAL_ANSWERS as $enum) {
        $codes = array_column($enum::cases(), 'value');
        expect($codes)->toContain('prefer_not_to_say');

        foreach (['en', 'ne'] as $locale) {
            app()->setLocale($locale);
            foreach ($enum::cases() as $case) {
                expect($case->getLabel())->not->toContain('.');
            }
        }
    }
})->group('P6');

it('accepts a case with every optional answer left out or blank, and refuses an unknown answer', function () {
    $blank = array_fill_keys(array_keys(Grievance::OPTIONAL_ANSWERS), null);

    expect(Validator::make(caseInput(), StoreGrievanceRequest::caseRules())->passes())->toBeTrue()
        ->and(Validator::make(caseInput($blank), StoreGrievanceRequest::caseRules())->passes())->toBeTrue()
        ->and(Validator::make(caseInput(['sex' => 'prefer_not_to_say', 'age_group' => '18_29']), StoreGrievanceRequest::caseRules())->passes())->toBeTrue()
        ->and(Validator::make(caseInput(['sex' => 'unknown']), StoreGrievanceRequest::caseRules())->errors()->keys())->toBe(['sex']);
})->group('P6');

it('shows the optional answers on the case page by their labels', function () {
    app()->setLocale('en'); // As SetPanelLocale does for every panel request.
    signedInAs(Role::Administrator);
    $case = Grievance::factory()->create(['complainant_type' => 'worker', 'sex' => 'prefer_not_to_say', 'age_group' => '30_44', 'ethnicity' => null, 'disability' => 'no']);

    Livewire::test(ViewGrievance::class, ['record' => $case->id])
        ->assertSee('Worker')->assertSee('Prefer not to say')->assertSee('30–44')->assertSee('Not given')
        ->assertDontSee('prefer_not_to_say');
})->group('P6');

it('shows a remembered month as the month and year only', function () {
    app()->setLocale('en');
    signedInAs(Role::Administrator);
    $case = Grievance::factory()->create(['incident_date' => '2026-06-01', 'incident_date_exact' => false]);

    Livewire::test(ViewGrievance::class, ['record' => $case->id])
        ->assertSee('June 2026, exact day not remembered');
})->group('P5');
