<?php

use App\Support\BsDate;
use Carbon\CarbonImmutable;

it('converts the company\'s own example both ways', function () {
    expect(BsDate::toAd(2083, 7, 22)->toDateString())->toBe('2026-11-08')
        ->and(BsDate::fromAd(CarbonImmutable::parse('2026-11-08')))->toBe([2083, 7, 22]);
})->group('U9', 'P5');

it('starts each Nepali year on its New Year\'s day', function (int $year, string $ad) {
    expect(BsDate::toAd($year, 1, 1)->toDateString())->toBe($ad);
})->with([[2080, '2023-04-14'], [2081, '2024-04-13'], [2082, '2025-04-14'], [2083, '2026-04-14']])->group('U9', 'P5');

it('turns every day of the table into AD and back unchanged, each year 365 or 366 days long', function () {
    $day = CarbonImmutable::parse('2013-04-14'); // 1 Baisakh 2070, the table's first day

    for ($year = BsDate::FIRST_YEAR; $year <= BsDate::LAST_YEAR; $year++) {
        $length = 0;
        for ($month = 1; $month <= 12; $month++) {
            for ($date = 1; $date <= BsDate::daysInMonth($year, $month); $date++, $length++, $day = $day->addDay()) {
                expect(BsDate::toAd($year, $month, $date)->toDateString())->toBe($day->toDateString())
                    ->and(BsDate::fromAd($day))->toBe([$year, $month, $date]);
            }
        }
        expect($length)->toBeIn([365, 366]);
    }

    expect($day->toDateString())->toBe('2034-04-14'); // the day after the table ends
})->group('U9', 'P5');

it('reads a moment by its Nepal date, not UTC', function () {
    // 00:15 on 9 November in Kathmandu is still 8 November in UTC.
    expect(BsDate::fromAd(CarbonImmutable::parse('2026-11-08 18:30', 'UTC')))->toBe([2083, 7, 23]);
})->group('U9', 'P5');

it('refuses a date the calendar does not have', function (int $year, int $month, int $day) {
    BsDate::toAd($year, $month, $day);
})->with([
    'Kartik 2083 has 30 days' => [2083, 7, 31],
    'no month 13' => [2083, 13, 1],
    'no day 0' => [2083, 7, 0],
    'before the table' => [2069, 12, 30],
    'after the table' => [2091, 1, 1],
])->throws(InvalidArgumentException::class)->group('U9', 'P5');

it('refuses an AD date outside the table', function () {
    BsDate::fromAd(CarbonImmutable::parse('2034-04-14'));
})->throws(InvalidArgumentException::class)->group('U9', 'P5');

it('writes a date for staff as BS with AD alongside, by Nepal time, and in AD only outside the table', function () {
    expect(BsDate::forStaff(CarbonImmutable::parse('2026-11-08 10:05')))->toBe('2083-07-22 (2026-11-08)')
        ->and(BsDate::forStaff(CarbonImmutable::parse('2026-11-08 10:05'), time: true))->toBe('2083-07-22 (2026-11-08) 10:05')
        ->and(BsDate::forStaff(CarbonImmutable::parse('2026-11-08 18:30', 'UTC'), time: true))->toBe('2083-07-23 (2026-11-09) 00:15')
        ->and(BsDate::forStaff(CarbonImmutable::parse('2207-05-13')))->toBe('2207-05-13');
})->group('U9');

it('writes a BS date alone for the export, by Nepal time, and nothing outside the table', function () {
    expect(BsDate::format(CarbonImmutable::parse('2026-11-08 18:30', 'UTC')))->toBe('2083-07-23')
        ->and(BsDate::format(CarbonImmutable::parse('2207-05-13')))->toBeNull();
})->group('U9', 'N4');
