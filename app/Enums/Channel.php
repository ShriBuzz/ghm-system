<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a grievance reached the company (A6), as the company lists it (U8). Web is set by the
 * public form; staff choose one of the others for an offline case.
 */
enum Channel: string implements HasLabel
{
    case Web = 'web';
    case Phone = 'phone';
    case Email = 'email';
    case DropBox = 'drop_box';
    case InPerson = 'in_person';
    case Letter = 'letter';
    case Meeting = 'meeting';
    case Referred = 'referred';
    case Other = 'other';

    public function getLabel(): string
    {
        return __("channel.{$this->value}");
    }
}
