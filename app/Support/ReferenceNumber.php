<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ReferenceNumber
{
    /**
     * The next case reference, GHM-YYYY-NNNN, restarting each Nepal year.
     *
     * One statement creates the year's row at 1 or moves it up by one, and LAST_INSERT_ID()
     * hands back this connection's number. Submissions made at the same moment queue on that
     * row until the outermost transaction commits; the earlier insert-then-update deadlocked
     * them (MySQL error 1213), so one of them failed.
     */
    public static function next(): string
    {
        $year = now()->year;

        DB::statement(
            'INSERT INTO reference_counters (year, last_number) VALUES (?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)',
            [$year],
        );

        return sprintf('GHM-%d-%04d', $year, (int) DB::scalar('SELECT LAST_INSERT_ID()'));
    }
}
