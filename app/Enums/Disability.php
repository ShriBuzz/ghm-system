<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Whether the complainant has a disability (P6), as the company lists it (U8). Codes are stored
 * encrypted, so never change one once real answers exist; labels can change any time.
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
