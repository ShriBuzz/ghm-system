<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The complainant's age group (P6), as the company lists it (U8). "Under 18" stays its own
 * group: it points to possible child labour. Codes are stored encrypted, so never change one
 * once real answers exist; labels can change any time.
 */
enum AgeGroup: string implements HasLabel
{
    case Under18 = 'under_18';
    case From18To24 = '18_24';
    case From25To34 = '25_34';
    case From35To44 = '35_44';
    case From45To59 = '45_59';
    case From60 = '60_plus';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return __("age_group.{$this->value}");
    }
}
