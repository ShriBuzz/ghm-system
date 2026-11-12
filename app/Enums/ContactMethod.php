<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a named complainant prefers to be contacted (P4), as the company lists it (U8).
 */
enum ContactMethod: string implements HasLabel
{
    case Phone = 'phone';
    case Email = 'email';
    case DoNotContact = 'do_not_contact';

    public function getLabel(): string
    {
        return __("contact.{$this->value}");
    }
}
