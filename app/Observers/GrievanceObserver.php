<?php

namespace App\Observers;

use App\Models\Grievance;

class GrievanceObserver
{
    /**
     * A case is restricted when its category is restricted or a user marked it so (A5).
     */
    public function saving(Grievance $grievance): void
    {
        $grievance->is_restricted = $grievance->manually_restricted
            || (bool) $grievance->category()->value('is_restricted');
    }
}
