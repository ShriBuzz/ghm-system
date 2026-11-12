<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

/**
 * Bikram Sambat (BS) to AD and back, by Nepal date (P5, U9). BS months follow no formula, so
 * their lengths come from a table. It was taken from two open calendars that agree on every
 * month, nepali-date-converter 3.4.2 (MIT) and bikram-sambat 1.8.1 (Apache-2.0), and checked
 * against the company's own example, 2083-07-22 = 2026-11-08.
 *
 * shortcut: the table runs from BS 2070 to 2090 (April 2013 to April 2034 AD); add the next
 * years from the government's published calendar before 2034.
 */
final class BsDate
{
    public const FIRST_YEAR = 2070;

    public const LAST_YEAR = 2090;

    /** 1 Baisakh 2070. */
    private const FIRST_DAY = '2013-04-14';

    /** Days in each month, Baisakh to Chaitra. */
    private const MONTH_DAYS = [
        2070 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2071 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2072 => [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2073 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2074 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2075 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2076 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2077 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2078 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2079 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2080 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2081 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2082 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2083 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2084 => [31, 31, 32, 31, 31, 30, 30, 30, 29, 30, 30, 30],
        2085 => [31, 32, 31, 32, 30, 31, 30, 30, 29, 30, 30, 30],
        2086 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2087 => [31, 31, 32, 31, 31, 31, 30, 30, 29, 30, 30, 30],
        2088 => [30, 31, 32, 32, 30, 31, 30, 30, 29, 30, 30, 30],
        2089 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2090 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
    ];

    /**
     * The AD date of a BS date.
     *
     * @throws InvalidArgumentException when the calendar has no such date
     */
    public static function toAd(int $year, int $month, int $day): CarbonImmutable
    {
        if ($day < 1 || $day > self::daysInMonth($year, $month)) {
            throw new InvalidArgumentException("No BS date {$year}-{$month}-{$day}.");
        }

        $days = $day - 1;
        for ($y = self::FIRST_YEAR; $y < $year; $y++) {
            $days += array_sum(self::MONTH_DAYS[$y]);
        }
        $days += array_sum(array_slice(self::MONTH_DAYS[$year], 0, $month - 1));

        return self::firstDay()->addDays($days);
    }

    /**
     * The BS date of the Nepal day a moment falls on, as [year, month, day].
     *
     * @return array{int, int, int}
     *
     * @throws InvalidArgumentException when the date is outside the table
     */
    public static function fromAd(CarbonInterface $date): array
    {
        $nepal = CarbonImmutable::parse($date->copy()->setTimezone(Config::string('app.timezone'))->toDateString());
        $days = (int) self::firstDay()->diffInDays($nepal);

        if ($days >= 0) {
            foreach (self::MONTH_DAYS as $year => $months) {
                foreach ($months as $index => $length) {
                    if ($days < $length) {
                        return [$year, $index + 1, $days + 1];
                    }
                    $days -= $length;
                }
            }
        }

        throw new InvalidArgumentException("{$nepal->toDateString()} is outside the BS table.");
    }

    /**
     * @throws InvalidArgumentException when the calendar has no such month
     */
    public static function daysInMonth(int $year, int $month): int
    {
        return self::MONTH_DAYS[$year][$month - 1] ?? throw new InvalidArgumentException("No BS month {$year}-{$month}.");
    }

    private static function firstDay(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::FIRST_DAY, Config::string('app.timezone'));
    }
}
