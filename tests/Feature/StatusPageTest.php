<?php

use App\Enums\AlertKind;
use App\Enums\MessageDirection;
use App\Enums\Role;
use App\Enums\Status;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\GrievanceNote;
use App\Models\User;
use App\Notifications\CaseAlert;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;

// Factory cases have the PIN 123456.
beforeEach(function () {
    $this->travelTo('2026-11-02 10:00');
    Notification::fake();
});

function lookUp(Grievance $case, string $pin = '123456', string $locale = 'en'): TestResponse
{
    return test()->post("/{$locale}/status", ['reference' => $case->reference, 'pin' => $pin]);
}

it('shows the status and the company\'s replies, never internal notes or staff names', function () {
    $staff = User::factory()->create(['name' => 'Hari Staffname', 'role' => Role::FocalPerson]);
    $case = Grievance::factory()->create();
    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Staff, 'author_id' => $staff->id, 'body' => 'We are looking into it.']);
    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Complainant, 'body' => 'It happened again today.']);
    GrievanceNote::factory()->for($case)->create(['user_id' => $staff->id, 'body' => 'Internal: speak to the supervisor']);
    $case->forceFill(['status' => Status::UnderAssessment])->save();

    lookUp($case)->assertRedirect(route('status.show', ['locale' => 'en']));
    $page = $this->get('/en/status/case')->assertOk();

    expect($page->headers->get('Cache-Control'))->toContain('no-store');
    $page->assertSee($case->reference)->assertSee('<dd class="status-value">Under assessment</dd>', escape: false)
        ->assertSee('We are looking into it.')->assertSee('Grievance team')
        ->assertSee('It happened again today.')
        ->assertDontSee('Internal: speak to the supervisor')->assertDontSee('Hari Staffname');
})->group('P8', 'A3');

it('lets the complainant add information, which alerts the staff who can see the case', function () {
    $focal = User::factory()->create(['role' => Role::FocalPerson]);
    $case = Grievance::factory()->create();

    lookUp($case);
    $this->post('/en/status/case', ['body' => 'The supervisor has done it again.'])
        ->assertRedirect(route('status.show', ['locale' => 'en']));

    $message = GrievanceMessage::sole();
    expect($message->direction)->toBe(MessageDirection::Complainant)->and($message->body)->toBe('The supervisor has done it again.')
        ->and($message->author_id)->toBeNull();
    Notification::assertSentTo($focal, CaseAlert::class, fn (CaseAlert $alert) => $alert->kind === AlertKind::Message);
    $this->get('/en/status/case')->assertSee('The supervisor has done it again.')->assertSee('Your message was sent.');
})->group('P8', 'N1');

it('takes a message on a closed case too, so an appeal reaches staff', function () {
    $case = Grievance::factory()->create();
    $case->forceFill(['status' => Status::Closed])->save();

    lookUp($case);
    $this->post('/en/status/case', ['body' => 'I do not agree with the outcome.'])
        ->assertRedirect(route('status.show', ['locale' => 'en']));

    expect(GrievanceMessage::sole()->body)->toBe('I do not agree with the outcome.');
})->group('P8', 'U12');

it('works for a restricted case too: the restriction is about staff, not the complainant', function () {
    $case = Grievance::factory()->create(['category_id' => Category::factory()->create(['is_restricted' => true])]);

    lookUp($case)->assertRedirect(route('status.show', ['locale' => 'en']));
    $this->get('/en/status/case')->assertOk()->assertSee($case->reference);
})->group('P8', 'A5');

it('answers an unknown reference exactly like a wrong PIN', function () {
    $case = Grievance::factory()->create();

    $wrongPin = $this->from('/en/status')->post('/en/status', ['reference' => $case->reference, 'pin' => '000000']);
    $wrongPinPage = $this->get('/en/status')->getContent();
    $unknown = $this->from('/en/status')->post('/en/status', ['reference' => 'GHM-2026-9999', 'pin' => '123456']);
    $unknownPage = $this->get('/en/status')->getContent();

    expect($wrongPin->headers->get('Location'))->toBe($unknown->headers->get('Location'))
        ->and($wrongPinPage)->toContain('The reference number or PIN is not right.')
        ->and($unknownPage)->toContain('The reference number or PIN is not right.');
})->group('P8');

it('locks a reference for 15 minutes after 5 wrong PINs, then opens it again', function () {
    $case = Grievance::factory()->create();

    foreach (range(1, 5) as $ignored) {
        lookUp($case, '000000');
    }
    lookUp($case)->assertRedirect(route('status.lookup', ['locale' => 'en']));
    $this->get('/en/status')->assertSee('locked for 15 minutes');

    $this->travel(15)->minutes();
    lookUp($case)->assertRedirect(route('status.show', ['locale' => 'en']));
})->group('P8');

it('counts each lookup before its slow PIN check, so lookups sent at once are counted too', function () {
    $case = Grievance::factory()->create();
    $wrongPins = 'status-wrong-pins:'.hash('sha256', $case->reference);
    $countedBeforeCheck = [];
    Hash::shouldReceive('check')->andReturnUsing(function () use ($wrongPins, &$countedBeforeCheck): bool {
        $countedBeforeCheck[] = RateLimiter::attempts($wrongPins);

        return false;
    });

    foreach (range(1, 6) as $ignored) {
        lookUp($case, '000000');
    }

    // Five checks, each already counted; the sixth finds the reference locked and is not checked.
    expect($countedBeforeCheck)->toBe([1, 2, 3, 4, 5]);
})->group('P8');

it('refuses a lookup that arrives while five are still being checked, without checking it', function () {
    $case = Grievance::factory()->create();
    // Five lookups counted, their PINs still being checked (bcrypt takes about 0.4 s).
    foreach (range(1, 5) as $ignored) {
        RateLimiter::hit('status-wrong-pins:'.hash('sha256', $case->reference), 15 * 60);
    }
    Hash::shouldReceive('check')->never();

    lookUp($case)->assertRedirect(route('status.lookup', ['locale' => 'en']));
    $this->get('/en/status')->assertSee('locked for 15 minutes');
})->group('P8');

it('stops one visitor guessing across many references, not another visitor', function () {
    $case = Grievance::factory()->create();

    foreach (range(1, 10) as $guess) {
        $this->post('/en/status', ['reference' => sprintf('GHM-2026-%04d', 5000 + $guess), 'pin' => '000000']);
    }
    lookUp($case)->assertRedirect(route('status.lookup', ['locale' => 'en']));
    $this->get('/en/status')->assertSee('Too many wrong tries')->assertSee('Try again in 60 minutes')->assertSee('We could not open your grievance');

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20']);
    lookUp($case)->assertRedirect(route('status.show', ['locale' => 'en']));
})->group('P8', 'S5');

it('never puts the reference or PIN in an address', function () {
    $case = Grievance::factory()->create();

    $addresses = [
        lookUp($case, '000000')->headers->get('Location'),
        lookUp($case)->headers->get('Location'),
        $this->post('/en/status/case', ['body' => 'More information.'])->headers->get('Location'),
        $this->post('/en/status/close')->headers->get('Location'),
    ];

    expect(implode(' ', $addresses))->not->toContain($case->reference)->not->toContain('123456')->not->toContain('000000');
})->group('P8');

it('closes the case after 10 idle minutes or on Close, keeping a reply typed too late', function () {
    $case = Grievance::factory()->create();

    lookUp($case);
    $this->travel(11)->minutes();
    $this->get('/en/status/case')->assertRedirect(route('status.lookup', ['locale' => 'en']));

    lookUp($case);
    $this->travel(11)->minutes();
    $this->post('/en/status/case', ['body' => 'Typed while the page sat open.'])->assertRedirect(route('status.lookup', ['locale' => 'en']));
    expect(GrievanceMessage::count())->toBe(0);
    lookUp($case);
    $this->get('/en/status/case')->assertSee('Typed while the page sat open.');

    $this->post('/en/status/close')->assertRedirect(route('status.lookup', ['locale' => 'en']));
    $this->get('/en/status/case')->assertRedirect(route('status.lookup', ['locale' => 'en']));
})->group('P8');

it('needs a reference and a PIN, and a reply with something in it', function () {
    $case = Grievance::factory()->create();

    $this->from('/en/status')->post('/en/status', ['reference' => '', 'pin' => '']);
    $this->get('/en/status')->assertSee('Write the reference number')->assertSee('Write the PIN');

    lookUp($case);
    $this->from('/en/status/case')->post('/en/status/case', ['body' => '']);
    $html = $this->get('/en/status/case')->assertSee('Write your message')->assertSee('<title>Error:', false)->getContent();
    // Under the heading, above the case and its thread, not at the foot of the page, so it is in view without JavaScript.
    expect(strpos($html, 'data-error-summary'))->toBeLessThan(strpos($html, 'class="facts"'));
    expect(GrievanceMessage::count())->toBe(0);
})->group('P8', 'S9');

it('keeps a late reply only for its own grievance, never showing it on another one opened on the same phone', function () {
    $first = Grievance::factory()->create();
    $second = Grievance::factory()->create();

    lookUp($first);
    $this->travel(11)->minutes();
    $this->post('/en/status/case', ['body' => 'Private words about the first grievance.']);

    lookUp($second);
    $this->get('/en/status/case')->assertDontSee('Private words about the first grievance.');

    lookUp($first);
    $this->get('/en/status/case')->assertSee('Private words about the first grievance.');
})->group('P8', 'S5');

it('never sends the PIN back to the page, even when the form comes back with an error', function () {
    $this->from('/en/status')->post('/en/status', ['reference' => '', 'pin' => '482913']);

    $this->get('/en/status')->assertSee('Write the reference number')->assertDontSee('482913');
})->group('P8');

it('takes 10 messages an hour for a grievance, then asks the complainant to wait, keeping the text', function () {
    $case = Grievance::factory()->create();
    $focal = User::factory()->create(['role' => Role::FocalPerson]); // After the case, so only message alerts count.
    lookUp($case);

    foreach (range(1, 10) as $number) {
        $this->post('/en/status/case', ['body' => "Message {$number}"]);
    }
    $this->post('/en/status/case', ['body' => 'The eleventh message']);

    expect(GrievanceMessage::count())->toBe(10);
    Notification::assertSentToTimes($focal, CaseAlert::class, 10);
    $this->get('/en/status/case')->assertSee('The eleventh message')->assertSee('many messages');

    $this->travel(61)->minutes();
    lookUp($case);
    $this->post('/en/status/case', ['body' => 'An hour later']);
    expect(GrievanceMessage::count())->toBe(11);
})->group('P8', 'N1');

it('accepts the reference and PIN as people type them: Nepali digits, spaces, small letters, no hyphens', function () {
    $case = Grievance::factory()->create();
    [, $year, $number] = explode('-', $case->reference);

    foreach ([[strtolower($case->reference), '१२३४५६'], ["ghm {$year} {$number}", '123 456'], ["GHM{$year}{$number}", ' 123456 ']] as [$reference, $pin]) {
        $this->post('/en/status', ['reference' => $reference, 'pin' => $pin])->assertRedirect(route('status.show', ['locale' => 'en']));
        $this->post('/en/status/close');
    }
})->group('P8');

it('counts wrong PINs against the one reference, however it was typed', function () {
    $case = Grievance::factory()->create();

    // The last is in full-width characters, which MySQL's collation treats as the plain ones.
    foreach ([$case->reference, strtolower($case->reference), str_replace('-', '', $case->reference), str_replace('-', ' ', $case->reference), mb_convert_kana($case->reference, 'A')] as $typed) {
        $this->post('/en/status', ['reference' => $typed, 'pin' => '000000']);
    }

    lookUp($case)->assertRedirect(route('status.lookup', ['locale' => 'en']));
    $this->get('/en/status')->assertSee('locked for 15 minutes');
})->group('P8');

it('shows a staff reply written in Nepali exactly as written, line break included', function () {
    $case = Grievance::factory()->create();
    GrievanceMessage::factory()->for($case)->create(['direction' => MessageDirection::Staff, 'body' => "तपाईंको गुनासो हेर्दैछौं।\nभोलि सम्पर्क गर्नेछौं।"]);

    lookUp($case, locale: 'ne')->assertRedirect(route('status.show', ['locale' => 'ne']));

    $this->get('/ne/status/case')->assertOk()->assertSee('तपाईंको गुनासो हेर्दैछौं।<br />', false)->assertSee('भोलि सम्पर्क गर्नेछौं।');
})->group('P8', 'U16');

it('also hides the reference and PIN page on Finish and hide, so they are not left on a shared phone', function () {
    $this->post('/en', formInput())->assertRedirect(route('grievance.received', ['locale' => 'en']));
    preg_match('/data-pin>(\d{6})</', $this->get('/en/received')->getContent(), $pin);

    lookUp(Grievance::sole(), $pin[1])->assertRedirect(route('status.show', ['locale' => 'en']));
    $this->post('/en/status/close')->assertRedirect(route('status.lookup', ['locale' => 'en']));

    $this->get('/en/received')->assertRedirect(route('home', ['locale' => 'en']));
})->group('P7', 'P8');
