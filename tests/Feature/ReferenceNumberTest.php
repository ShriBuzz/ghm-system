<?php

use App\Support\Pin;
use App\Support\ReferenceNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('numbers cases GHM-YYYY-NNNN in order', function () {
    $this->travelTo('2026-11-20 10:00');

    expect(ReferenceNumber::next())->toBe('GHM-2026-0001')
        ->and(ReferenceNumber::next())->toBe('GHM-2026-0002');
})->group('P7');

it('restarts the sequence each year, by Nepal date', function () {
    $this->travelTo('2026-12-31 12:00');
    ReferenceNumber::next();

    // 31 Dec 20:00 UTC is already 1 Jan in Kathmandu.
    $this->travelTo(Carbon::parse('2026-12-31 20:00', 'UTC'));

    expect(ReferenceNumber::next())->toBe('GHM-2027-0001');
})->group('P7');

it('never hands out the same number twice, inside an outer transaction too', function () {
    $numbers = collect(range(1, 50))->map(fn () => DB::transaction(fn () => ReferenceNumber::next()));

    expect($numbers->unique())->toHaveCount(50);
})->group('P7');

it('makes a random PIN of the configured length', function () {
    $pins = collect(range(1, 50))->map(fn () => Pin::generate());

    expect($pins->every(fn ($pin) => preg_match('/^\d{6}$/', $pin) === 1))->toBeTrue()
        ->and($pins->unique()->count())->toBeGreaterThan(45);

    config(['ghm.pin_length' => 8]);
    expect(Pin::generate())->toMatch('/^\d{8}$/');
})->group('P7');
