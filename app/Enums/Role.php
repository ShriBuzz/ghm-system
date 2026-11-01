<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    case Administrator = 'administrator';
    case FocalPerson = 'focal_person';
    case RestrictedOfficer = 'restricted_officer';

    public function getLabel(): string
    {
        return __("role.{$this->value}");
    }
}
