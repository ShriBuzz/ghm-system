<?php

use App\Enums\MessageDirection;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\GrievanceMessage;
use App\Models\GrievanceNote;
use App\Models\User;
use App\Notifications\CaseAlert;
use Illuminate\Support\Facades\Notification;
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

    lookUp($case)->assertRedirect(route('status.show', ['locale' => 'en']));
    $page = $this->get('/en/status/case')->assertOk();

    expect($page->headers->get('Cache-Control'))->toContain('no-store');
    $page->assertSee($case->reference)->assertSee('Received')->assertSee('We are looking into it.')->assertSee('Grievance team')
        ->assertSee('It happened again today.')->assertSee('noindex', false)
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
    Notification::assertSentTo($focal, CaseAlert::class, fn (CaseAlert $alert) => $alert->message);
    $this->get('/en/status/case')->assertSee('The supervisor has done it again.')->assertSee('Your message was sent.');
})->group('P8', 'N1');

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

it('stops one visitor guessing across many references, not another visitor', function () {
    $case = Grievance::factory()->create();

    foreach (range(1, 10) as $guess) {
        $this->post('/en/status', ['reference' => sprintf('GHM-2026-%04d', 5000 + $guess), 'pin' => '000000']);
    }
    lookUp($case)->assertRedirect(route('status.lookup', ['locale' => 'en']));
    $this->get('/en/status')->assertSee('Too many attempts');

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
    $this->get('/en/status/case')->assertSee('Write your message');
    expect(GrievanceMessage::count())->toBe(0);
})->group('P8', 'S9');
