<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a grievance reached the company (A6). Kick-off default: clarification question 7.
 */
enum Channel: string implements HasLabel
{
    case Web = 'web';
    case Phone = 'phone';
    case Email = 'email';
    case DropBox = 'drop_box';
    case InPerson = 'in_person';

    public function getLabel(): string
    {
        return __("channel.{$this->value}");
    }
}
