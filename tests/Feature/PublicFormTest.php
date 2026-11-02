<?php

use App\Models\Attachment;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use App\Models\Setting;
use App\Notifications\Acknowledgement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo('2026-11-02 10:00');
    Notification::fake();
});

/**
 * What the public form sends for a valid anonymous grievance, as strings, like a browser.
 *
 * @return array<string, mixed>
 */
function formInput(array $overrides = []): array
{
    return [
        'is_anonymous' => '1',
        'category_id' => (string) Category::factory()->create()->id,
        'location_id' => (string) Location::factory()->create()->id,
        'incident_day' => '28',
        'incident_month' => '10',
        'incident_year' => '2026',
        'recurrence' => 'once',
        'description' => 'Wages unpaid for three months',
        ...$overrides,
    ];
}

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

it('gives a reference and a PIN, stores only the PIN\'s hash, and shows both until Done', function () {
    $this->post('/ne', formInput())->assertRedirect(route('grievance.received', ['locale' => 'ne']));
    $case = Grievance::sole();

    $page = $this->get('/ne/received')->assertOk()->assertHeader('Cache-Control')->getContent();
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
    $this->post('/en', formInput(['sex' => 'prefer_not_to_say', 'age_group' => '30_44']))->assertSessionHasNoErrors();

    [$first, $second] = Grievance::orderBy('id')->get()->all();
    expect($first->sex)->toBeNull()->and($second->sex)->toBe('prefer_not_to_say')->and($second->age_group)->toBe('30_44');
})->group('P6');

it('keeps what was typed when something needs fixing, with a summary that links to the field', function () {
    $this->from('/en')->post('/en', formInput(['description' => 'Wages unpaid since June', 'category_id' => '']))
        ->assertRedirect('/en');

    $html = $this->get('/en')->getContent();
    expect($html)->toContain('Wages unpaid since June')->toContain('href="#category_id"');
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
