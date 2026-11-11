<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The complainant's age group (P6). Placeholder options until NBI confirms the list.
 * Codes are stored encrypted, so settle them before production; labels can change any time.
 */
enum AgeGroup: string implements HasLabel
{
    case Under18 = 'under_18';
    case From18To29 = '18_29';
    case From30To44 = '30_44';
    case From45To59 = '45_59';
    case From60 = '60_plus';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return __("age_group.{$this->value}");
    }
}
