<?php

namespace App\Support;

use App\Models\Holiday;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Config;

class WorkingDays
{
    /**
     * Working days after $from, up to and including $to, by Nepal date (A2).
     * The weekly day off comes from config, public holidays from the holidays table.
     *
     * @return int<0, max>
     */
    public static function between(CarbonInterface $from, CarbonInterface $to): int
    {
        $timezone = Config::string('app.timezone');
        $start = $from->copy()->setTimezone($timezone)->startOfDay()->addDay();
        $end = $to->copy()->setTimezone($timezone)->startOfDay();

        if ($start->gt($end)) {
            return 0;
        }

        $weekend = Config::array('ghm.weekend_days');
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn (CarbonInterface $date): string => $date->toDateString())
            ->flip();

        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if (! in_array($day->dayOfWeek, $weekend, true) && ! $holidays->has($day->toDateString())) {
                $days++;
            }
        }

        return $days;
    }
}
