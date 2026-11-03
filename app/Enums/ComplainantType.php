<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Who the complainant is (P6). Placeholder options until NBI answers clarification question 5.
 * Codes are stored encrypted, so settle them before production; labels can change any time.
 */
enum ComplainantType: string implements HasLabel
{
    case Worker = 'worker';
    case ContractorOrSupplier = 'contractor_supplier';
    case CommunityMember = 'community_member';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return __("complainant_type.{$this->value}");
    }
}
