<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The complainant's sex (P6). Placeholder options until NBI confirms the list.
 * Codes are stored encrypted, so settle them before production; labels can change any time.
 */
enum Sex: string implements HasLabel
{
    case Female = 'female';
    case Male = 'male';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return __("sex.{$this->value}");
    }
}
