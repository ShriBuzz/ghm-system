<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The complainant's caste or ethnicity (P6), as the company lists it (U8). Codes are stored
 * encrypted, so never change one once real answers exist; labels can change any time.
 */
enum Ethnicity: string implements HasLabel
{
    case BrahminChhetri = 'brahmin_chhetri';
    case Janajati = 'janajati';
    case Dalit = 'dalit';
    case Madhesi = 'madhesi';
    case Tharu = 'tharu';
    case Muslim = 'muslim';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return __("ethnicity.{$this->value}");
    }
}
