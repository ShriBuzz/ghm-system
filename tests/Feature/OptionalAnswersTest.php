<?php

use App\Enums\AgeGroup;
use App\Enums\ComplainantType;
use App\Enums\Disability;
use App\Enums\Ethnicity;
use App\Enums\Role;
use App\Enums\Sex;
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
                expect($case->getLabel())->not->toMatch('/^[a-z_]+\.[a-z0-9_]+$/'); // an untranslated key
            }
        }
    }
})->group('P6');

it('offers the company\'s lists, in its order and words', function () {
    $labels = function (string $enum, string $locale): array {
        app()->setLocale($locale);

        return array_map(fn ($answer) => $answer->getLabel(), $enum::cases());
    };

    expect($labels(ComplainantType::class, 'en'))->toBe([
        'Permanent employee', 'Temporary or probationary employee', 'Contract worker, e.g. security, cleaning, driver',
        'Former employee', 'Supplier or supply-chain worker', 'Customer', 'Community member', 'Trade union',
        'Worker representative', 'Civil society organisation', 'Other', 'Prefer not to say',
    ])
        ->and($labels(Sex::class, 'en'))->toBe(['Female', 'Male', 'Other', 'Prefer not to say'])
        ->and($labels(AgeGroup::class, 'en'))->toBe(['Under 18', '18–24', '25–34', '35–44', '45–59', '60 and above', 'Prefer not to say'])
        ->and($labels(Ethnicity::class, 'en'))->toBe([
            'Brahmin/Chhetri', 'Janajati / Indigenous Nationalities', 'Dalit', 'Madhesi', 'Tharu', 'Muslim', 'Other', 'Prefer not to say',
        ])
        ->and($labels(Disability::class, 'en'))->toBe(['Yes', 'No', 'Prefer not to say'])
        ->and($labels(AgeGroup::class, 'ne'))->toBe(['१८ वर्षभन्दा कम', '१८–२४', '२५–३४', '३५–४४', '४५–५९', '६० वर्ष वा माथि', 'भन्न चाहँदिन'])
        ->and($labels(Disability::class, 'ne'))->toBe(['हो', 'होइन', 'भन्न चाहँदिन'])
        ->and($labels(ComplainantType::class, 'ne')[2])->toBe('करार कामदार, जस्तै सुरक्षाकर्मी, सरसफाई, चालक आदि')
        ->and($labels(Ethnicity::class, 'ne')[4])->toBe('थारु');
})->group('P6', 'U8');

it('accepts a case with every optional answer left out or blank, and refuses an unknown answer', function () {
    $blank = array_fill_keys(array_keys(Grievance::OPTIONAL_ANSWERS), null);

    expect(Validator::make(caseInput(), StoreGrievanceRequest::caseRules())->passes())->toBeTrue()
        ->and(Validator::make(caseInput($blank), StoreGrievanceRequest::caseRules())->passes())->toBeTrue()
        ->and(Validator::make(caseInput(['sex' => 'prefer_not_to_say', 'age_group' => '18_24']), StoreGrievanceRequest::caseRules())->passes())->toBeTrue()
        ->and(Validator::make(caseInput(['sex' => 'unknown']), StoreGrievanceRequest::caseRules())->errors()->keys())->toBe(['sex']);
})->group('P6');

it('shows the optional answers on the case page by their labels', function () {
    app()->setLocale('en'); // As SetPanelLocale does for every panel request.
    signedInAs(Role::Administrator);
    $case = Grievance::factory()->create(['complainant_type' => 'permanent_employee', 'sex' => 'prefer_not_to_say', 'age_group' => '35_44', 'ethnicity' => null, 'disability' => 'no']);

    Livewire::test(ViewGrievance::class, ['record' => $case->id])
        ->assertSee('Permanent employee')->assertSee('Prefer not to say')->assertSee('35–44')->assertSee('Not given')
        ->assertDontSee('prefer_not_to_say');
})->group('P6');

it('shows a remembered Bikram Sambat month as that month, not the AD month it starts in', function () {
    app()->setLocale('en');
    signedInAs(Role::Administrator);
    $case = Grievance::factory()->create(['incident_date' => '2026-10-18', 'incident_date_exact' => false, 'incident_date_calendar' => 'bs']);

    Livewire::test(ViewGrievance::class, ['record' => $case->id])
        ->assertSee('Kartik 2083, exact day not remembered')->assertDontSee('October 2026');
})->group('P5', 'U9');

it('shows a remembered month as the month and year only', function () {
    app()->setLocale('en');
    signedInAs(Role::Administrator);
    $case = Grievance::factory()->create(['incident_date' => '2026-06-01', 'incident_date_exact' => false]);

    Livewire::test(ViewGrievance::class, ['record' => $case->id])
        ->assertSee('June 2026, exact day not remembered');
})->group('P5');

it('says who sees the optional answers and why, never that they show who is complaining', function () {
    $this->get('/en')->assertSee('These answers show which groups of people raise grievances. Only the staff who handle grievances see them. Skip any question.')
        ->assertDontSee('see who raises grievances');
    $this->get('/ne')->assertSee('यी उत्तरले कस्ता समूहका मानिसले गुनासो दिन्छन् भनेर देखाउँछन्। गुनासो हेर्ने कर्मचारीले मात्र यी उत्तर देख्छन्। चाहेको प्रश्न छोड्न सक्नुहुन्छ।');
})->group('P6');
