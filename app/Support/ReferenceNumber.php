<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ReferenceNumber
{
    /**
     * The next case reference, GHM-YYYY-NNNN, restarting each Nepal year (P7).
     *
     * The counter row stays locked until the outermost transaction commits, so
     * two submissions in the same second never share a number.
     */
    public static function next(): string
    {
        return DB::transaction(function (): string {
            $year = now()->year;

            DB::table('reference_counters')->insertOrIgnore(['year' => $year, 'last_number' => 0]);
            DB::table('reference_counters')->where('year', $year)->increment('last_number');
            $number = DB::table('reference_counters')->where('year', $year)->value('last_number');

            return sprintf('GHM-%d-%04d', $year, $number);
        });
    }
}
