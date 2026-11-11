<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a named complainant prefers to be contacted (P4). Kick-off default until NBI confirms the list.
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
