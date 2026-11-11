<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The complainant's ethnic group (P6). Placeholder options until NBI confirms the list.
 * Codes are stored encrypted, so settle them before production; labels can change any time.
 */
enum Ethnicity: string implements HasLabel
{
    case BrahminChhetri = 'brahmin_chhetri';
    case Janajati = 'janajati';
    case Madhesi = 'madhesi';
    case Dalit = 'dalit';
    case Muslim = 'muslim';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return __("ethnicity.{$this->value}");
    }
}
