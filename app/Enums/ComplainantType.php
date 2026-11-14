<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Who the complainant is. Codes are stored encrypted, so never change one once real answers
 * exist; labels can change any time.
 */
enum ComplainantType: string implements HasLabel
{
    case PermanentEmployee = 'permanent_employee';
    case TemporaryEmployee = 'temporary_employee';
    case ContractWorker = 'contract_worker';
    case FormerEmployee = 'former_employee';
    case SupplierWorker = 'supplier_worker';
    case Customer = 'customer';
    case CommunityMember = 'community_member';
    case TradeUnion = 'trade_union';
    case WorkerRepresentative = 'worker_representative';
    case CivilSociety = 'civil_society';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return __("complainant_type.{$this->value}");
    }
}
