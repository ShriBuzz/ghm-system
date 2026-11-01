<?php

namespace App\Observers;

use App\Enums\Status;
use App\Models\Grievance;
use App\Support\WorkingDays;
use BackedEnum;

class GrievanceObserver
{
    /**
     * Case fields whose changes go into the activity log (S6), with the event name.
     * Only codes, IDs and flags: never encrypted text.
     */
    private const LOGGED = [
        'status' => 'status_changed',
        'priority' => 'priority_changed',
        'assigned_to' => 'assigned',
        'manually_restricted' => 'restriction_changed',
    ];

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

    public function created(Grievance $grievance): void
    {
        activity()->performedOn($grievance)->event('created')
            ->withProperties(['channel' => $grievance->channel->value])
            ->log('Case created');

        $grievance->alertStaff();
    }

    public function updated(Grievance $grievance): void
    {
        foreach (self::LOGGED as $field => $event) {
            if ($grievance->wasChanged($field)) {
                $plain = fn (mixed $value): mixed => $value instanceof BackedEnum ? $value->value : $value;

                activity()->performedOn($grievance)->event($event)
                    ->withProperties(['old' => $plain($grievance->getOriginal($field)), 'new' => $plain($grievance->getAttribute($field))])
                    ->log(str($event)->headline()->toString());
            }
        }
    }

    /**
     * Runs after the save, so the visibility rule reads the case's new restriction.
     */
    public function saved(Grievance $grievance): void
    {
        if ($grievance->wasChanged(['is_restricted', 'assigned_to'])) {
            $grievance->dropUnseeingAssignee();
        }
    }

    /**
     * Days open run while the case is open and freeze when it stops (A2).
     * A reopened case counts again from the day it was received.
     */
    private function updateClock(Grievance $grievance): void
    {
        $was = $grievance->getOriginal('status');

        // Any status can follow Closed, so the date goes whenever the case leaves it.
        $grievance->closed_at = $grievance->status === Status::Closed ? now() : null;

        if (! $was instanceof Status || $was->isOpen() || $grievance->status->isOpen()) {
            $grievance->working_days_open = WorkingDays::between($grievance->received_at, now());
        }
    }
}
