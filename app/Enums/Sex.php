<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The complainant's sex. Codes are stored encrypted, so never change one once real answers
 * exist; labels can change any time.
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
