<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Whether the complainant has a disability (P6). Placeholder options until NBI confirms the list.
 * Codes are stored encrypted, so settle them before production; labels can change any time.
 */
enum Disability: string implements HasLabel
{
    case Yes = 'yes';
    case No = 'no';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return __("disability.{$this->value}");
    }
}
