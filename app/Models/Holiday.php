<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['date', 'name'])]
class Holiday extends Model
{
    /**
     * Every change to the list is logged, so the Administrator can check what the focal
     * person entered (A2, S6). A holiday's date and name are not personal data.
     */
    protected static function booted(): void
    {
        $log = fn (Holiday $holiday, string $event, array $extra = []) => activity()->performedOn($holiday)->event($event)
            ->withProperties(['date' => $holiday->date->toDateString(), 'name' => $holiday->name, ...$extra])
            ->log(str($event)->headline()->toString());

        static::created(fn (Holiday $holiday) => $log($holiday, 'holiday_added'));
        static::updated(fn (Holiday $holiday) => $log($holiday, 'holiday_changed', [
            'before' => $holiday->getOriginal('date')->toDateString().' '.$holiday->getOriginal('name'),
        ]));
        static::deleted(fn (Holiday $holiday) => $log($holiday, 'holiday_deleted'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
