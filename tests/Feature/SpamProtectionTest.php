<?php

use App\Models\Grievance;
use App\Support\VisitorKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo('2026-11-02 10:00');
    Notification::fake();
});

it('refuses a submission whose hidden trap field was filled, and keeps what was typed', function () {
    // The page the visitor lands on, not assertSessionHasErrors: with JSON sessions that assertion
    // empties the flashed errors before the next request in the same test.
    $this->from('/en')->post('/en', formInput(['website' => 'https://spam.example', 'description' => 'Typed text to keep']))
        ->assertRedirect('/en');

    expect(Grievance::count())->toBe(0);
    $this->get('/en')->assertSee('Typed text to keep')->assertSee('was not sent');
})->group('P9');

it('refuses a form sent faster than a person can fill it, or with a missing or forged time', function () {
    $justNow = Crypt::encryptString((string) now()->subSeconds(2)->getTimestamp());

    $this->post('/en', formInput(['form_started' => $justNow]))->assertSessionHasErrors('form');
    $this->post('/en', formInput(['form_started' => '']))->assertSessionHasErrors('form');
    $this->post('/en', formInput(['form_started' => (string) now()->subMinute()->getTimestamp()]))->assertSessionHasErrors('form');
    expect(Grievance::count())->toBe(0);

    $this->post('/en', formInput(['form_started' => Crypt::encryptString((string) now()->subSeconds(3)->getTimestamp())]))
        ->assertSessionHasNoErrors();
    expect(Grievance::count())->toBe(1);
})->group('P9');

it('refuses the 11th grievance in an hour from one visitor, not from another, and allows it an hour later', function () {
    foreach (range(1, 10) as $ignored) {
        $this->post('/en', formInput())->assertSessionHasNoErrors();
    }

    $this->from('/en')->post('/en', formInput(['description' => 'The eleventh']))->assertSessionHasErrors('form');
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])->post('/en', formInput())->assertSessionHasNoErrors();

    $this->travel(61)->minutes();
    $this->post('/en', formInput())->assertSessionHasNoErrors();

    expect(Grievance::count())->toBe(12);
})->group('P9');

it('keys visitors without their IP address, by a salt that is thrown away at midnight', function () {
    $request = Request::create('/en', 'POST', server: ['REMOTE_ADDR' => '203.0.113.7']);
    $key = VisitorKey::for($request);

    expect($key)->not->toContain('203.0.113.7')->toHaveLength(64)
        ->and(VisitorKey::for($request))->toBe($key)
        ->and(VisitorKey::for(Request::create('/en', 'POST', server: ['REMOTE_ADDR' => '203.0.113.8'])))->not->toBe($key);

    $this->travelTo('2026-11-03 00:01');
    expect(VisitorKey::for($request))->not->toBe($key);

    $this->post('/en', formInput());
    $cached = (fn () => json_encode($this->storage))->call(Cache::store()->getStore());
    expect($cached)->not->toContain('127.0.0.1');
})->group('P9', 'S5');
