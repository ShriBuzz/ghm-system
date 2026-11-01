<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Case statuses exactly as RFQ A4 lists them. Labels live in lang/*.json.
 */
enum Status: string implements HasLabel
{
    case Received = 'received';
    case UnderAssessment = 'under_assessment';
    case UnderInvestigation = 'under_investigation';
    case ReferredOutOfScope = 'referred_out_of_scope';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';

    /**
     * Whether the 15-working-day clock runs in this status (A2). Kick-off default.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Received, self::UnderAssessment, self::UnderInvestigation, self::Reopened], true);
    }

    public function getLabel(): string
    {
        return __("status.{$this->value}");
    }
}
