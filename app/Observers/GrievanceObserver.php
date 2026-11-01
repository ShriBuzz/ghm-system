<?php

namespace App\Observers;

use App\Enums\Status;
use App\Models\Grievance;
use App\Support\WorkingDays;

class GrievanceObserver
{
    /**
     * A case is restricted when its category is restricted or a user marked it so (A5).
     */
    public function saving(Grievance $grievance): void
    {
        $grievance->is_restricted = $grievance->manually_restricted
            || (bool) $grievance->category()->value('is_restricted');

        if ($grievance->isDirty('status')) {
            $this->updateClock($grievance);
        }
    }

    /**
     * Days open run while the case is open and freeze when it stops (A2).
     * A reopened case counts again from the day it was received.
     */
    private function updateClock(Grievance $grievance): void
    {
        $was = $grievance->getOriginal('status');

        if ($grievance->status === Status::Closed) {
            $grievance->closed_at = now();
        } elseif ($grievance->status === Status::Reopened) {
            $grievance->closed_at = null;
        }

        if (! $was instanceof Status || $was->isOpen() || $grievance->status->isOpen()) {
            $grievance->working_days_open = WorkingDays::between($grievance->received_at, now());
        }
    }
}
