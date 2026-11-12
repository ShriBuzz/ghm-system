<?php

use App\Enums\ContactMethod;
use App\Models\Attachment;
use App\Models\Grievance;
use App\Models\Setting;
use App\Notifications\Acknowledgement;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo('2026-11-02 10:00');
    Notification::fake();
});

it('opens the form to anyone, with the notice above the first question, escaped', function () {
    $this->get('/en')->assertOk()->assertSee('[Draft:', false)->assertSee('name="is_anonymous"', false);

    Setting::put('notice.en', "Kept <b>confidential</b>.\n\nNo retaliation.");
    $html = $this->get('/en')->assertOk()->getContent();

    expect($html)->toContain('Kept &lt;b&gt;confidential&lt;/b&gt;.')->toContain('<p>No retaliation.</p>')
        ->and(strpos($html, 'No retaliation.'))->toBeLessThan(strpos($html, 'name="is_anonymous"'));

    $this->get('/ne')->assertOk()->assertSee('[मस्यौदा:', false);
})->group('P1', 'P3');

it('keeps an anonymous case free of contact details, even when some were typed', function () {
    $this->post('/en', formInput(['name' => 'Ram', 'phone' => '9800000000', 'email' => 'ram@example.com', 'preferred_contact' => 'email']))
        ->assertRedirect(route('grievance.received', ['locale' => 'en']));

    $case = Grievance::sole();
    expect($case->is_anonymous)->toBeTrue()
        ->and([$case->name, $case->phone, $case->email, $case->preferred_contact])->toBe([null, null, null, null])
        ->and($case->channel->value)->toBe('web')
        ->and($case->locale)->toBe('en');
    Notification::assertNothingSent();
})->group('P4', 'S5');

it('needs a name, and the contact its preferred method names, from a named complainant', function () {
    $this->post('/en', formInput(['is_anonymous' => '0', 'preferred_contact' => 'email', 'phone' => '9800000000']))
        ->assertSessionHasErrors(['name', 'email']);

    expect(Grievance::count())->toBe(0);
})->group('P4');

it('lets a named complainant ask not to be contacted, yet still needs a phone or an email', function () {
    $this->post('/en', formInput(['is_anonymous' => '0', 'name' => 'Sita', 'preferred_contact' => 'do_not_contact']))
        ->assertSessionHasErrors(['phone' => 'Give a phone number or an email.']);
    expect(Grievance::count())->toBe(0);

    $this->post('/en', formInput(['is_anonymous' => '0', 'name' => 'Sita', 'email' => 'sita@example.com', 'preferred_contact' => 'do_not_contact']))
        ->assertSessionHasNoErrors();

    $case = Grievance::sole();
    expect($case->preferred_contact)->toBe(ContactMethod::DoNotContact);
    // P7: the reference and PIN still go to the email given; they are the complainant's only copy besides the screen.
    Notification::assertSentTo($case, Acknowledgement::class);
})->group('P4', 'P7', 'U8');

it('words the identity, contact and frequency choices as the company does, in both languages', function () {
    $this->get('/en')->assertOk()->assertSeeInOrder(['Without my name (anonymous)', 'With my name'])
        ->assertSee('Do not contact me')->assertSee('Repeatedly')->assertSee('Caste/ethnicity');

    $this->get('/ne')->assertOk()->assertSeeInOrder(['नाम नखुलाई (बेनामी)', 'नाम सहित'])
        ->assertSee('मलाई सम्पर्क नगर्नुहोस्')->assertSee('घटना कतिपटक भएको हो?')->assertSee('पटक-पटक')
        ->assertSee('तपाईं को हुनुहुन्छ?')->assertSee('जाति/जातीयता')->assertSee('भन्न चाहँदिन');
})->group('P4', 'P5', 'P6', 'U8');

/**
 * The form as the Nepali page sends it: the incident date as a Bikram Sambat day, month and year.
 *
 * @return array<string, mixed>
 */
function nepaliFormInput(string $day, string $month, string $year): array
{
    return array_diff_key(formInput(), array_flip(['incident_day', 'incident_month', 'incident_year']))
        + ['incident_bs_day' => $day, 'incident_bs_month' => $month, 'incident_bs_year' => $year];
}

it('asks for the incident date in Bikram Sambat on the Nepali form and in AD on the English one', function () {
    $this->travelTo('2026-11-12 10:00');

    $this->get('/ne')->assertOk()->assertSee('name="incident_bs_month"', false)->assertDontSee('name="incident_month"', false)
        ->assertSeeInOrder(['बैशाख', 'कार्तिक', 'चैत'])->assertSee('<option value="2083" selected>', false);
    $this->get('/en')->assertOk()->assertSee('name="incident_month"', false)->assertDontSee('name="incident_bs_month"', false);
})->group('P5', 'U9');

it('stores a Bikram Sambat incident date in AD, and a remembered month as its first day', function () {
    $this->travelTo('2026-11-12 10:00');

    $this->post('/ne', nepaliFormInput('22', '7', '2083'))->assertSessionHasNoErrors();
    $this->post('/ne', nepaliFormInput('unknown', '7', '2083'))->assertSessionHasNoErrors();
    $this->post('/en', formInput())->assertSessionHasNoErrors();

    expect(Grievance::orderBy('id')->get()->map->only('incident_date_exact', 'incident_date_calendar')->all())->toBe([
        ['incident_date_exact' => true, 'incident_date_calendar' => 'bs'],
        ['incident_date_exact' => false, 'incident_date_calendar' => 'bs'],
        ['incident_date_exact' => true, 'incident_date_calendar' => 'ad'],
    ])->and(Grievance::orderBy('id')->get()->map(fn (Grievance $case) => $case->incident_date->toDateString())->all())
        ->toBe(['2026-11-08', '2026-10-18', '2026-10-28']);
})->group('P5', 'U9');

it('refuses a Bikram Sambat date that does not exist, is still to come, or is outside the calendar', function (array $date, string $message) {
    $this->travelTo('2026-11-12 10:00');

    $this->post('/ne', nepaliFormInput(...$date))->assertSessionHasErrors(['incident_date' => $message]);

    expect(Grievance::count())->toBe(0);
})->with([
    'Kartik 2083 has 30 days' => [['31', '7', '2083'], 'यो मिति हुँदैन। गते र महिना जाँच्नुहोस्।'],
    '15 Mangsir 2083 is 1 December 2026' => [['15', '8', '2083'], 'यो मिति अझै आएको छैन। घटना भएको दिन छान्नुहोस्।'],
    'a year the calendar does not have' => [['1', '1', '2091'], 'यो मिति हुँदैन। गते र महिना जाँच्नुहोस्।'],
])->group('P5', 'U9');

it('gives a reference and a PIN, stores only the PIN\'s hash, and shows both until Done', function () {
    $this->post('/ne', formInput())->assertRedirect(route('grievance.received', ['locale' => 'ne']));
    $case = Grievance::sole();

    $received = $this->get('/ne/received')->assertOk();
    expect($received->headers->get('Cache-Control'))->toContain('no-store');
    $page = $received->getContent();
    preg_match('/data-pin>(\d{6})</', $page, $pin);

    expect($case->reference)->toMatch('/^GHM-2026-\d{4}$/')
        ->and($page)->toContain($case->reference)->toContain('noindex')
        ->and($pin)->toHaveCount(2)
        ->and(Hash::check($pin[1], $case->pin_hash))->toBeTrue()
        ->and($case->pin_hash)->not->toContain($pin[1]);

    // A reload on the phone keeps them; Done ends them.
    $this->get('/en/received')->assertOk()->assertSee($pin[1]);
    $this->post('/en/received')->assertRedirect(route('home', ['locale' => 'en']));
    $this->get('/en/received')->assertRedirect(route('home', ['locale' => 'en']));
})->group('P7');

it('emails the reference and PIN only when an email was given', function () {
    $this->post('/en', formInput(['is_anonymous' => '0', 'name' => 'Sita', 'phone' => '9800000000', 'preferred_contact' => 'phone']));
    Notification::assertNothingSent();

    $this->post('/en', formInput(['is_anonymous' => '0', 'name' => 'Sita', 'email' => 'sita@example.com', 'preferred_contact' => 'email']));
    Notification::assertSentTo(Grievance::latest('id')->first(), Acknowledgement::class);
})->group('P7', 'N2');

it('takes a remembered month as the month only, and refuses an impossible or future date', function () {
    $this->post('/en', formInput(['incident_day' => 'unknown', 'incident_month' => '9']))->assertSessionHasNoErrors();
    $case = Grievance::sole();
    expect($case->incident_date->toDateString())->toBe('2026-09-01')->and($case->incident_date_exact)->toBeFalse();

    $this->post('/en', formInput(['incident_day' => '30', 'incident_month' => '2']))->assertSessionHasErrors('incident_date');
    $this->post('/en', formInput(['incident_day' => '3', 'incident_month' => '11']))->assertSessionHasErrors('incident_date');
    $this->post('/en', formInput(['incident_day' => '']))->assertSessionHasErrors('incident_date');
})->group('P5');

it('stores one photo or PDF up to 5 MB, and refuses a larger file or a renamed program', function () {
    $this->post('/en', formInput(['attachment' => UploadedFile::fake()->image('photo.jpg', 800, 600)]))->assertSessionHasNoErrors();
    expect(Attachment::sole()->original_name)->toBe('photo.jpg');

    $this->post('/en', formInput(['attachment' => UploadedFile::fake()->create('big.pdf', 5121, 'application/pdf')]))
        ->assertSessionHasErrors('attachment');
    $this->post('/en', formInput(['attachment' => UploadedFile::fake()->createWithContent('photo.jpg', "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xff\xff")]))
        ->assertSessionHasErrors('attachment');

    expect(Grievance::count())->toBe(1);
})->group('P5', 'S4');

it('accepts every optional question left blank, and stores the answers given', function () {
    $blank = array_fill_keys(array_keys(Grievance::OPTIONAL_ANSWERS), '');
    $this->post('/en', formInput($blank))->assertSessionHasNoErrors();
    $this->post('/en', formInput(['sex' => 'prefer_not_to_say', 'age_group' => '35_44']))->assertSessionHasNoErrors();

    [$first, $second] = Grievance::orderBy('id')->get()->all();
    expect($first->sex)->toBeNull()->and($second->sex)->toBe('prefer_not_to_say')->and($second->age_group)->toBe('35_44');
})->group('P6');

it('keeps what was typed when something needs fixing, with a summary that links to the field', function () {
    $this->from('/en')->post('/en', formInput(['description' => 'Wages unpaid since June', 'category_id' => '']))
        ->assertRedirect('/en');

    $html = $this->get('/en')->getContent();
    expect($html)->toContain('Wages unpaid since June')->toContain('href="#category_id"')
        // In view without JavaScript too: under the heading, above the notice, and named in the title.
        ->toContain('<title>Error: Send a grievance')
        ->and(strpos($html, 'data-error-summary'))->toBeLessThan(strpos($html, 'notice-heading'));
})->group('P1', 'S9');

it('labels every field and puts every set of choices in a fieldset with a legend', function () {
    $dom = new DOMDocument;
    @$dom->loadHTML($this->get('/en')->getContent());
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//form[@data-grievance]//input[not(@type="hidden") and not(@type="radio")] | //form[@data-grievance]//select | //form[@data-grievance]//textarea') as $field) {
        $id = $field->getAttribute('id');
        expect($id)->not->toBe('')
            ->and($xpath->query("//label[@for='{$id}']")->length)->toBe(1, "No label for #{$id}");
    }
    foreach ($xpath->query('//form[@data-grievance]//input[@type="radio"]') as $radio) {
        expect($xpath->query('ancestor::fieldset/legend', $radio)->length)->toBeGreaterThan(0);
    }
})->group('S9');

it('stores no IP address anywhere the submission writes', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->post('/en', formInput());

    $everything = collect(['grievances', 'activity_log', 'jobs', 'attachments'])
        ->flatMap(fn (string $table) => DB::table($table)->get()->map(fn ($row) => json_encode($row)))->implode(' ');
    expect(Grievance::count())->toBe(1)->and($everything)->not->toContain('203.0.113.7');
})->group('S5');

it('sends a form posted after the session ended back to it, with what was typed', function () {
    // Browsers that send Sec-Fetch-Site pass the forgery check without the session; older ones fail it
    // with a token mismatch, which reaches the exception handler as a 419 for this route.
    $request = Request::create('/en', 'POST', formInput(['description' => 'Typed before the session ended', 'website' => 'x']));
    $route = app('router')->getRoutes()->match($request);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    $response = app(ExceptionHandler::class)->render($request, new TokenMismatchException);

    // What the redirect flashes; the form refills from old input as in the test above.
    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(url('/en'))
        ->and(session()->getOldInput('description'))->toBe('Typed before the session ended')
        ->and(session()->getOldInput('form_started'))->not->toBeNull()
        ->and(session()->getOldInput('website'))->toBeNull()
        ->and(session('errors')->getBag('default')->first('form'))->toStartWith('This page was open too long to send');
})->group('S2', 'P1');

it('refuses crafted list values in place of text, without an error page', function () {
    $this->post('/en', formInput(['incident_day' => ['1']]))->assertRedirect()->assertSessionHasErrors('incident_date');
    $this->post('/en', formInput(['form_started' => ['x']]))->assertRedirect()->assertSessionHasErrors('form');

    expect(Grievance::count())->toBe(0);
})->group('P9', 'P5');
