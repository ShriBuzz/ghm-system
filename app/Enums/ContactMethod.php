<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a named complainant prefers to be contacted (P4). Kick-off default: clarification question 6.
 */
enum ContactMethod: string implements HasLabel
{
    case Phone = 'phone';
    case Email = 'email';

    public function getLabel(): string
    {
        return __("contact.{$this->value}");
    }
}
